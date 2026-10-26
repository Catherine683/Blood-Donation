<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SlotFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $drive_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property int $capacity
 * @property int $booked_count
 * @property int $remaining
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['drive_id', 'starts_at', 'ends_at', 'capacity', 'booked_count'])]
class Slot extends Model
{
    /** @use HasFactory<SlotFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'drive_id' => 'integer',
            'capacity' => 'integer',
            'booked_count' => 'integer',
        ];
    }

    /**
     * Start time attribute (stored strictly in UTC).
     *
     * @return Attribute<CarbonImmutable, CarbonImmutable|string>
     */
    protected function startsAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? CarbonImmutable::parse($value, 'UTC') : null,
            set: fn ($value) => $value instanceof DateTimeInterface
                ? CarbonImmutable::instance($value)->setTimezone('UTC')->format('Y-m-d H:i:s')
                : ($value ? CarbonImmutable::parse((string) $value, 'UTC')->format('Y-m-d H:i:s') : null),
        );
    }

    /**
     * End time attribute (stored strictly in UTC).
     *
     * @return Attribute<CarbonImmutable, CarbonImmutable|string>
     */
    protected function endsAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? CarbonImmutable::parse($value, 'UTC') : null,
            set: fn ($value) => $value instanceof DateTimeInterface
                ? CarbonImmutable::instance($value)->setTimezone('UTC')->format('Y-m-d H:i:s')
                : ($value ? CarbonImmutable::parse((string) $value, 'UTC')->format('Y-m-d H:i:s') : null),
        );
    }

    /**
     * Drive this slot belongs to.
     *
     * @return BelongsTo<Drive, $this>
     */
    public function drive(): BelongsTo
    {
        return $this->belongsTo(Drive::class);
    }

    /**
     * Appointments booked for this slot.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Remaining capacity accessor (capacity - booked_count, never negative).
     *
     * @return Attribute<int, never>
     */
    protected function remaining(): Attribute
    {
        return Attribute::make(
            get: fn (): int => max(0, (int) $this->capacity - (int) $this->booked_count),
        );
    }

    /**
     * Helper to get remaining capacity.
     */
    public function getRemainingAttribute(): int
    {
        return max(0, (int) $this->capacity - (int) $this->booked_count);
    }

    /**
     * Check if slot is upcoming (starts in the future).
     */
    public function isUpcoming(): bool
    {
        return $this->starts_at->isFuture();
    }

    /**
     * Check if slot has already started or ended.
     */
    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }
}
