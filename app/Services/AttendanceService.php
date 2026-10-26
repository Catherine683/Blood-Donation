<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DriveStatus;
use App\Events\AppointmentCancelled;
use App\Exceptions\CloseOutBlockedException;
use App\Exceptions\NotAllowedException;
use App\Models\Appointment;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Check in a donor for their appointment on the drive day.
     * Allowed for a booked appointment whose drive is published or closed and whose slot's date is today (EAT) or the drive is currently running.
     * Repeating is an idempotent no-op.
     */
    public function checkIn(Appointment $appointment, User $staff): Appointment
    {
        return DB::transaction(function () use ($appointment, $staff) {
            $locked = Appointment::query()
                ->where('id', $appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotent: already checked in
            if ($locked->checked_in_at !== null) {
                return $locked;
            }

            if ($locked->status !== AppointmentStatus::Booked) {
                throw new NotAllowedException('Only booked appointments can be checked in.');
            }

            $slot = $locked->slot;
            $drive = $slot?->drive;

            if (! $drive || ! ($drive->isPublished() || $drive->isClosed())) {
                throw new NotAllowedException('Check-in is only allowed for published or closed blood drives.');
            }

            $todayEat = DateHelper::today();
            $slotDateEat = Carbon::parse($slot->starts_at)->setTimezone('Africa/Kampala')->toDateString();
            $driveStartsEat = Carbon::parse($drive->starts_on->toDateString(), 'Africa/Kampala');
            $driveEndsEat = Carbon::parse($drive->ends_on->toDateString(), 'Africa/Kampala');

            $isTodaySlot = $slotDateEat === $todayEat->toDateString();
            $isDriveRunning = $todayEat->gte($driveStartsEat) && $todayEat->lte($driveEndsEat);

            if (! ($isTodaySlot || $isDriveRunning)) {
                throw new NotAllowedException('Check-in is only permitted on the day of the slot or while the drive is active.');
            }

            $locked->checked_in_at = Carbon::now('UTC');
            $locked->save();

            AuditLogger::record($staff, 'appointment.checked_in', $locked, [
                'appointment_id' => $locked->id,
                'slot_id' => $locked->slot_id,
                'user_id' => $locked->user_id,
            ]);

            return $locked;
        });
    }

    /**
     * Undo a check-in. Allowed only if no outcome is recorded yet.
     */
    public function undoCheckIn(Appointment $appointment, User $staff): Appointment
    {
        return DB::transaction(function () use ($appointment, $staff) {
            $locked = Appointment::query()
                ->where('id', $appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->donationRecord()->exists()) {
                throw new NotAllowedException('Cannot undo check-in once an outcome has been recorded.');
            }

            // Idempotent
            if ($locked->checked_in_at === null) {
                return $locked;
            }

            $locked->checked_in_at = null;
            $locked->save();

            AuditLogger::record($staff, 'appointment.check_in_undone', $locked, [
                'appointment_id' => $locked->id,
                'slot_id' => $locked->slot_id,
                'user_id' => $locked->user_id,
            ]);

            return $locked;
        });
    }

    /**
     * Mark an appointment as no-show. Must not release slot capacity.
     */
    public function markNoShow(Appointment $appointment, User $staff): Appointment
    {
        return DB::transaction(function () use ($appointment, $staff) {
            $locked = Appointment::query()
                ->where('id', $appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === AppointmentStatus::NoShow) {
                return $locked;
            }

            if ($locked->donationRecord()->exists()) {
                throw new NotAllowedException('Cannot mark appointment as no-show once an outcome has been recorded.');
            }

            if ($locked->status !== AppointmentStatus::Booked) {
                throw new NotAllowedException('Only booked appointments can be marked as no-show.');
            }

            // Note: Does not release capacity; place was used up
            $locked->status = AppointmentStatus::NoShow;
            $locked->save();

            AuditLogger::record($staff, 'appointment.no_show', $locked, [
                'appointment_id' => $locked->id,
                'slot_id' => $locked->slot_id,
                'user_id' => $locked->user_id,
            ]);

            return $locked;
        });
    }

    /**
     * Cancel an appointment from the staff side with capacity release and email event.
     */
    public function cancelByStaff(Appointment $appointment, User $staff): Appointment
    {
        return DB::transaction(function () use ($appointment, $staff) {
            $locked = Appointment::query()
                ->where('id', $appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === AppointmentStatus::Cancelled) {
                return $locked;
            }

            if ($locked->donationRecord()->exists()) {
                throw new NotAllowedException('Cannot cancel an appointment that already has a recorded outcome.');
            }

            if ($locked->status !== AppointmentStatus::Booked) {
                throw new NotAllowedException('Only booked appointments can be cancelled by staff.');
            }

            // Atomically decrement slot booked_count (cannot drop below 0)
            Slot::query()
                ->where('id', $locked->slot_id)
                ->whereRaw('booked_count > 0')
                ->update([
                    'booked_count' => DB::raw('booked_count - 1'),
                    'updated_at' => Carbon::now('UTC'),
                ]);

            $locked->status = AppointmentStatus::Cancelled;
            $locked->cancelled_at = Carbon::now('UTC');
            $locked->save();

            AuditLogger::record($staff, 'appointment.cancelled_by_staff', $locked, [
                'appointment_id' => $locked->id,
                'slot_id' => $locked->slot_id,
                'user_id' => $locked->user_id,
            ]);

            DB::afterCommit(function () use ($locked) {
                event(new AppointmentCancelled($locked));
            });

            return $locked;
        });
    }

    /**
     * Close out a drive, marking remaining booked appointments as no-show.
     * Blocked while any appointment is checked in without a recorded outcome.
     *
     * @return array{no_show_count: int, drive: Drive}
     *
     * @throws CloseOutBlockedException
     * @throws NotAllowedException
     */
    public function closeOutDrive(Drive $drive, User $staff): array
    {
        return DB::transaction(function () use ($drive, $staff) {
            $lockedDrive = Drive::query()
                ->where('id', $drive->id)
                ->lockForUpdate()
                ->firstOrFail();

            $slots = $lockedDrive->slots()->get();
            $nowUtc = Carbon::now('UTC');
            $todayEat = DateHelper::today()->toDateString();
            $driveEndsEat = $lockedDrive->ends_on->format('Y-m-d');

            $allSlotsEnded = $slots->isNotEmpty() && $slots->every(fn (Slot $s) => $s->ends_at->lte($nowUtc));
            $endsOnPassed = $driveEndsEat < $todayEat;

            if (! ($allSlotsEnded || $endsOnPassed)) {
                throw new NotAllowedException('A drive can only be closed out after every slot has ended or its ends_on date has passed.');
            }

            // Check if any appointment is checked in but has no outcome
            $slotIds = $slots->pluck('id');
            $unresolvedAppointments = Appointment::query()
                ->whereIn('slot_id', $slotIds)
                ->where('status', AppointmentStatus::Booked)
                ->whereNotNull('checked_in_at')
                ->whereDoesntHave('donationRecord')
                ->with('user')
                ->get();

            if ($unresolvedAppointments->isNotEmpty()) {
                throw new CloseOutBlockedException(
                    $unresolvedAppointments->map(fn (Appointment $a) => [
                        'appointment_id' => $a->id,
                        'donor_name' => $a->user->name,
                        'phone' => $a->user->phone,
                    ])
                );
            }

            // Find all remaining booked appointments
            $remainingBooked = Appointment::query()
                ->whereIn('slot_id', $slotIds)
                ->where('status', AppointmentStatus::Booked)
                ->lockForUpdate()
                ->get();

            $noShowCount = $remainingBooked->count();

            if ($noShowCount > 0) {
                Appointment::query()
                    ->whereIn('id', $remainingBooked->pluck('id'))
                    ->update([
                        'status' => AppointmentStatus::NoShow->value,
                        'updated_at' => Carbon::now('UTC'),
                    ]);
            }

            $wasAlreadyClosed = $lockedDrive->isClosed();
            $lockedDrive->status = DriveStatus::Closed;
            $lockedDrive->save();

            // Record audit log entry (only if not already closed with 0 changes)
            if (! $wasAlreadyClosed || $noShowCount > 0) {
                AuditLogger::record($staff, 'drive.closed_out', $lockedDrive, [
                    'drive_id' => $lockedDrive->id,
                    'no_show_count' => $noShowCount,
                ]);
            }

            return [
                'no_show_count' => $noShowCount,
                'drive' => $lockedDrive,
            ];
        });
    }
}
