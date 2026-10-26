<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonationOutcome;
use Carbon\CarbonImmutable;
use Database\Factories\DonationRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $drive_id
 * @property int|null $appointment_id
 * @property DonationOutcome $outcome
 * @property int $recorded_by
 * @property CarbonImmutable $recorded_at
 * @property string|null $staff_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Drive $drive
 * @property-read Appointment|null $appointment
 * @property-read User $recordedBy
 */
#[Fillable(['user_id', 'drive_id', 'appointment_id', 'outcome', 'recorded_by', 'recorded_at', 'staff_note'])]
class DonationRecord extends Model
{
    /** @use HasFactory<DonationRecordFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'drive_id' => 'integer',
            'appointment_id' => 'integer',
            'outcome' => DonationOutcome::class,
            'recorded_by' => 'integer',
            'recorded_at' => 'immutable_datetime',
        ];
    }

    /**
     * Donor who attended.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Drive at which the outcome occurred.
     *
     * @return BelongsTo<Drive, $this>
     */
    public function drive(): BelongsTo
    {
        return $this->belongsTo(Drive::class);
    }

    /**
     * Appointment tied to this outcome (null for walk-ins).
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Staff member who recorded the outcome.
     *
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Check if donor successfully donated.
     */
    public function isDonated(): bool
    {
        return $this->outcome === DonationOutcome::Donated;
    }

    /**
     * Check if donor was deferred (did not donate).
     */
    public function isDeferred(): bool
    {
        return $this->outcome === DonationOutcome::Deferred;
    }

    /**
     * Check if this is a walk-in attendance (no booked appointment).
     */
    public function isWalkIn(): bool
    {
        return $this->appointment_id === null;
    }
}
