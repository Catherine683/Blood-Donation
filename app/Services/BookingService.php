<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\BookingWindowClosedException;
use App\Exceptions\DriveNotOpenException;
use App\Exceptions\NotAllowedException;
use App\Exceptions\SlotFullException;
use App\Models\Appointment;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Book a time slot for a verified donor.
     *
     * @throws SlotFullException
     * @throws DriveNotOpenException
     * @throws BookingWindowClosedException
     * @throws AlreadyBookedException
     * @throws NotAllowedException
     */
    public function book(User $donor, Slot $slot): Appointment
    {
        return DB::transaction(function () use ($donor, $slot) {
            // Serialise requests for this donor by locking their own user record
            $lockedDonor = User::query()
                ->where('id', $donor->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedDonor) {
                throw new NotAllowedException('User account not found.');
            }

            // 1. User must be an active donor with a verified email
            if (! $lockedDonor->is_active || ! $lockedDonor->isDonor() || ! $lockedDonor->hasVerifiedEmail()) {
                throw new NotAllowedException('Only active, email-verified voluntary donors can book appointments.');
            }

            // 2. Drive must be published and have not ended
            $drive = $slot->drive()->first();
            if (! $drive || ! $drive->isPublished() || $drive->isPast()) {
                throw new DriveNotOpenException('This blood donation drive is not open for appointment bookings.');
            }

            // 3. Slot must start at least booking_cutoff_minutes from now
            $cutoffMinutes = (int) config('donorlink.booking_cutoff_minutes', 60);
            $cutoffThreshold = Carbon::now('UTC')->addMinutes($cutoffMinutes);

            if ($slot->starts_at->isPast() || $slot->starts_at->lt($cutoffThreshold)) {
                throw new BookingWindowClosedException('Booking for this time slot has closed.');
            }

            // 4. Donor must have no active 'booked' appointment for any slot in the same drive
            $alreadyBooked = Appointment::query()
                ->where('user_id', $lockedDonor->id)
                ->where('status', AppointmentStatus::Booked->value)
                ->whereHas('slot', function ($query) use ($drive) {
                    $query->where('drive_id', $drive->id);
                })
                ->exists();

            if ($alreadyBooked) {
                throw new AlreadyBookedException('You already have an active appointment booked for this donation drive.');
            }

            // 5. Reserve place atomically with single conditional update (never read-then-write)
            $affected = DB::table('slots')
                ->where('id', $slot->id)
                ->whereRaw('booked_count < capacity')
                ->update([
                    'booked_count' => DB::raw('booked_count + 1'),
                    'updated_at' => Carbon::now('UTC'),
                ]);

            if ($affected === 0) {
                throw new SlotFullException('That slot has just been filled. Please choose another time.');
            }

            $slot->booked_count = (int) DB::table('slots')->where('id', $slot->id)->value('booked_count');

            // 6. Create appointment as booked
            $appointment = Appointment::forceCreate([
                'user_id' => $lockedDonor->id,
                'slot_id' => $slot->id,
                'status' => AppointmentStatus::Booked,
            ]);

            // 7. Dispatch AppointmentBooked after transaction commit
            DB::afterCommit(function () use ($appointment) {
                event(new AppointmentBooked($appointment));
            });

            return $appointment;
        }, 10);
    }

    /**
     * Cancel an existing booked appointment.
     * Idempotent: Cancelling an already cancelled appointment is a safe no-op.
     *
     * @throws NotAllowedException
     */
    public function cancel(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $lockedAppointment = Appointment::query()
                ->where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedAppointment) {
                throw new NotAllowedException('Appointment not found.');
            }

            // Only the owning donor can cancel
            if ($lockedAppointment->user_id !== $actor->id) {
                throw new NotAllowedException('You are not authorized to cancel another donor\'s appointment.');
            }

            // Idempotent: already cancelled releases no place and returns safely
            if ($lockedAppointment->status === AppointmentStatus::Cancelled) {
                return $lockedAppointment;
            }

            // Only currently 'booked' appointments can be cancelled
            if ($lockedAppointment->status !== AppointmentStatus::Booked) {
                throw new NotAllowedException('Only currently booked appointments can be cancelled.');
            }

            $slot = $lockedAppointment->slot;
            if ($slot && $slot->starts_at->lte(Carbon::now('UTC'))) {
                throw new NotAllowedException('Cannot cancel an appointment once the session start time has arrived.');
            }

            // Atomically release the place
            DB::table('slots')
                ->where('id', $lockedAppointment->slot_id)
                ->whereRaw('booked_count > 0')
                ->update([
                    'booked_count' => DB::raw('booked_count - 1'),
                    'updated_at' => Carbon::now('UTC'),
                ]);

            $lockedAppointment->status = AppointmentStatus::Cancelled;
            $lockedAppointment->cancelled_at = Carbon::now('UTC');
            $lockedAppointment->save();

            DB::afterCommit(function () use ($lockedAppointment) {
                event(new AppointmentCancelled($lockedAppointment));
            });

            return $lockedAppointment;
        }, 10);
    }

    /**
     * Reschedule an appointment to a new slot on the same drive.
     *
     * @throws SlotFullException
     * @throws DriveNotOpenException
     * @throws BookingWindowClosedException
     * @throws NotAllowedException
     */
    public function reschedule(Appointment $appointment, Slot $newSlot, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $newSlot, $actor) {
            $lockedDonor = User::query()
                ->where('id', $actor->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedDonor) {
                throw new NotAllowedException('User not found.');
            }

            $currentAppointment = Appointment::query()
                ->where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (! $currentAppointment) {
                throw new NotAllowedException('Appointment not found.');
            }

            if ($currentAppointment->user_id !== $actor->id) {
                throw new NotAllowedException('You are not authorized to reschedule another donor\'s appointment.');
            }

            if ($currentAppointment->status !== AppointmentStatus::Booked) {
                throw new NotAllowedException('Only active booked appointments can be rescheduled.');
            }

            $currentSlot = $currentAppointment->slot;
            if (! $currentSlot) {
                throw new NotAllowedException('Current appointment slot not found.');
            }

            if ($currentSlot->starts_at->lte(Carbon::now('UTC'))) {
                throw new NotAllowedException('Cannot reschedule an appointment once the session start time has arrived.');
            }

            // Same drive only
            if ($newSlot->drive_id !== $currentSlot->drive_id) {
                throw new NotAllowedException('Appointments can only be rescheduled to a slot within the same blood drive.');
            }

            // Cannot reschedule to the current slot
            if ($newSlot->id === $currentSlot->id) {
                throw new NotAllowedException('Please choose a different time slot to reschedule.');
            }

            // Drive must be published and not ended
            $drive = $newSlot->drive;
            if (! $drive || ! $drive->isPublished() || $drive->isPast()) {
                throw new DriveNotOpenException('This blood donation drive is not open for appointment bookings.');
            }

            // New slot cutoff window
            $cutoffMinutes = (int) config('donorlink.booking_cutoff_minutes', 60);
            $cutoffThreshold = Carbon::now('UTC')->addMinutes($cutoffMinutes);

            if ($newSlot->starts_at->isPast() || $newSlot->starts_at->lt($cutoffThreshold)) {
                throw new BookingWindowClosedException('Booking for the selected time slot has closed.');
            }

            $oldSlotId = $currentAppointment->slot_id;

            // Reserve the new place first (atomic conditional update)
            $affectedNew = DB::table('slots')
                ->where('id', $newSlot->id)
                ->whereRaw('booked_count < capacity')
                ->update([
                    'booked_count' => DB::raw('booked_count + 1'),
                    'updated_at' => Carbon::now('UTC'),
                ]);

            if ($affectedNew === 0) {
                throw new SlotFullException('That slot has just been filled. Please choose another time.');
            }

            // Release the old place atomically
            DB::table('slots')
                ->where('id', $oldSlotId)
                ->whereRaw('booked_count > 0')
                ->update([
                    'booked_count' => DB::raw('booked_count - 1'),
                    'updated_at' => Carbon::now('UTC'),
                ]);

            // Update slot_id on the same appointment row
            $currentAppointment->slot_id = $newSlot->id;
            $currentAppointment->save();

            DB::afterCommit(function () use ($currentAppointment, $oldSlotId) {
                event(new AppointmentRescheduled($currentAppointment, $oldSlotId));
            });

            return $currentAppointment;
        }, 10);
    }
}
