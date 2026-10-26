<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $slot_id
 * @property AppointmentStatus $status
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $checked_in_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Slot $slot
 * @property-read Drive|null $drive
 */
#[Fillable(['user_id', 'slot_id', 'status', 'cancelled_at', 'checked_in_at'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
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
            'slot_id' => 'integer',
            'status' => AppointmentStatus::class,
            'cancelled_at' => 'immutable_datetime',
            'checked_in_at' => 'immutable_datetime',
        ];
    }

    /**
     * Donor who owns this appointment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Slot reserved for this appointment.
     *
     * @return BelongsTo<Slot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Donation record linked to this appointment.
     *
     * @return HasOne<DonationRecord, $this>
     */
    public function donationRecord(): HasOne
    {
        return $this->hasOne(DonationRecord::class);
    }

    /**
     * Drive accessor through the slot.
     */
    public function getDriveAttribute(): ?Drive
    {
        return $this->slot?->drive;
    }

    /**
     * Direct method accessor for drive through slot.
     */
    public function drive(): ?Drive
    {
        return $this->slot?->drive;
    }

    /**
     * Check if appointment is currently booked.
     */
    public function isBooked(): bool
    {
        return $this->status === AppointmentStatus::Booked;
    }

    /**
     * Check if appointment has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::Cancelled;
    }

    /**
     * Scope for booked appointments.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeBooked(Builder $query): void
    {
        $query->where('status', AppointmentStatus::Booked->value);
    }
}
