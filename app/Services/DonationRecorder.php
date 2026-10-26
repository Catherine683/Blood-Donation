<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Exceptions\DonationRecordException;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\DonorProfile;
use App\Models\Drive;
use App\Models\User;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DonationRecorder
{
    /**
     * Record a donation outcome for an appointment or a walk-in donor.
     *
     * @throws DonationRecordException
     */
    public function record(
        User $donor,
        Drive $drive,
        DonationOutcome $outcome,
        User $staff,
        ?Appointment $appointment = null,
        ?string $note = null
    ): DonationRecord {
        return DB::transaction(function () use ($donor, $drive, $outcome, $staff, $appointment, $note) {
            // Enforce one record per donor per drive
            $alreadyExists = DonationRecord::query()
                ->where('user_id', $donor->id)
                ->where('drive_id', $drive->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyExists) {
                throw new DonationRecordException('A record already exists for this donor at this drive.');
            }

            if ($appointment !== null) {
                $lockedAppointment = Appointment::query()
                    ->where('id', $appointment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedAppointment || $lockedAppointment->user_id !== $donor->id) {
                    throw new DonationRecordException('The appointment does not belong to this donor.');
                }

                if ($lockedAppointment->slot?->drive_id !== $drive->id) {
                    throw new DonationRecordException('The appointment does not belong to this drive.');
                }

                if ($lockedAppointment->status !== AppointmentStatus::Booked) {
                    throw new DonationRecordException('Only booked appointments can have an outcome recorded.');
                }

                if ($lockedAppointment->donationRecord()->exists()) {
                    throw new DonationRecordException('A record already exists for this appointment.');
                }

                // Both donated and deferred set status to attended (the donor came)
                $lockedAppointment->status = AppointmentStatus::Attended;
                if ($lockedAppointment->checked_in_at === null) {
                    $lockedAppointment->checked_in_at = Carbon::now('UTC');
                }
                $lockedAppointment->save();
            } else {
                // Walk-in donor validation
                if (! $donor->is_active || ! $donor->isDonor()) {
                    throw new DonationRecordException('Walk-in donor must be an existing active donor account.');
                }

                if (! ($drive->isPublished() || $drive->isClosed())) {
                    throw new DonationRecordException('Drive must be published or closed to record walk-in donations.');
                }

                $todayEat = DateHelper::today();
                $graceDays = (int) config('donorlink.recording_grace_days', 7);
                $driveStartsOn = Carbon::parse($drive->starts_on->toDateString(), 'Africa/Kampala');
                $maxAllowedDate = Carbon::parse($drive->ends_on->toDateString(), 'Africa/Kampala')->addDays($graceDays);

                if ($todayEat->lt($driveStartsOn) || $todayEat->gt($maxAllowedDate)) {
                    throw new DonationRecordException('Walk-in donation can only be recorded within the drive dates or within the 7-day grace period.');
                }
            }

            $cleanNote = $note !== null && trim($note) !== ''
                ? mb_substr(trim($note), 0, 255)
                : null;

            $record = DonationRecord::forceCreate([
                'user_id' => $donor->id,
                'drive_id' => $drive->id,
                'appointment_id' => $appointment?->id,
                'outcome' => $outcome,
                'recorded_by' => $staff->id,
                'recorded_at' => Carbon::now('UTC'),
                'staff_note' => $cleanNote,
            ]);

            // Refresh donor's derived last_donated_on date
            DonorProfile::refreshLastDonation($donor);

            AuditLogger::record($staff, 'donation.recorded', $record, [
                'record_id' => $record->id,
                'donor_id' => $donor->id,
                'drive_id' => $drive->id,
                'outcome' => $outcome->value,
                'has_appointment' => $appointment !== null,
            ]);

            return $record;
        });
    }

    /**
     * Correct an existing donation record outcome and administrative note.
     */
    public function correct(
        DonationRecord $record,
        DonationOutcome $newOutcome,
        User $staff,
        ?string $note = null
    ): DonationRecord {
        return DB::transaction(function () use ($record, $newOutcome, $staff, $note) {
            $lockedRecord = DonationRecord::query()
                ->where('id', $record->id)
                ->lockForUpdate()
                ->firstOrFail();

            $changedFields = [];
            if ($lockedRecord->outcome !== $newOutcome) {
                $changedFields[] = 'outcome';
                $lockedRecord->outcome = $newOutcome;
            }

            $cleanNote = $note !== null
                ? (trim($note) === '' ? null : mb_substr(trim($note), 0, 255))
                : $lockedRecord->staff_note;

            if ($lockedRecord->staff_note !== $cleanNote) {
                $changedFields[] = 'staff_note';
                $lockedRecord->staff_note = $cleanNote;
            }

            $lockedRecord->save();

            // Refresh donor's derived last_donated_on date
            DonorProfile::refreshLastDonation($lockedRecord->user_id);

            AuditLogger::record($staff, 'donation.corrected', $lockedRecord, [
                'record_id' => $lockedRecord->id,
                'donor_id' => $lockedRecord->user_id,
                'drive_id' => $lockedRecord->drive_id,
                'changed_fields' => $changedFields,
            ]);

            return $lockedRecord;
        });
    }
}
