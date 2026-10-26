<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Drive;
use App\Models\Slot;
use App\Support\DateHelper;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SlotService
{
    /**
     * Create a new slot for a drive.
     * All times are entered in EAT and stored in UTC.
     *
     * @param  array{
     *     date?: string,
     *     start_time?: string,
     *     end_time?: string,
     *     starts_at?: string|DateTimeInterface,
     *     ends_at?: string|DateTimeInterface,
     *     capacity: int,
     * }  $data
     *
     * @throws ValidationException
     */
    public function create(Drive $drive, array $data): Slot
    {
        if ($drive->isPast()) {
            throw ValidationException::withMessages([
                'drive' => 'Cannot add slots to past drives.',
            ]);
        }

        [$startsAtUtc, $endsAtUtc] = $this->resolveUtcTimestamps($data);
        $capacity = (int) $data['capacity'];

        $this->validateSlotBounds($drive, $startsAtUtc, $endsAtUtc, $capacity);

        // Check if starts in the past
        if ($startsAtUtc->lte(Carbon::now('UTC'))) {
            throw ValidationException::withMessages([
                'starts_at' => 'You cannot create a slot in the past.',
            ]);
        }

        // Check uniqueness on (drive_id, starts_at)
        $existing = $drive->slots()
            ->where('starts_at', $startsAtUtc->format('Y-m-d H:i:s'))
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'starts_at' => 'A slot starting at this time already exists for this drive.',
            ]);
        }

        return $drive->slots()->create([
            'starts_at' => $startsAtUtc,
            'ends_at' => $endsAtUtc,
            'capacity' => $capacity,
            'booked_count' => 0,
        ]);
    }

    /**
     * Update an existing slot.
     *
     * @param  array{
     *     date?: string,
     *     start_time?: string,
     *     end_time?: string,
     *     starts_at?: string|DateTimeInterface,
     *     ends_at?: string|DateTimeInterface,
     *     capacity?: int,
     * }  $data
     *
     * @throws ValidationException
     */
    public function update(Slot $slot, array $data): Slot
    {
        $slot->refresh();
        $drive = $slot->drive;

        if ($drive->isPast()) {
            throw ValidationException::withMessages([
                'drive' => 'Cannot update slots for past drives.',
            ]);
        }

        $hasTimeData = isset($data['date']) || isset($data['start_time']) || isset($data['starts_at']);

        if ($hasTimeData) {
            [$startsAtUtc, $endsAtUtc] = $this->resolveUtcTimestamps($data, $slot);

            $timeChanged = ! $slot->starts_at->equalTo($startsAtUtc) || ! $slot->ends_at->equalTo($endsAtUtc);

            if ($timeChanged) {
                if ($slot->booked_count > 0) {
                    throw ValidationException::withMessages([
                        'booked_count' => 'Slot times cannot be modified because donors have already booked this slot.',
                    ]);
                }

                $this->validateSlotBounds($drive, $startsAtUtc, $endsAtUtc, (int) ($data['capacity'] ?? $slot->capacity));

                if ($startsAtUtc->lte(Carbon::now('UTC'))) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'Cannot update slot start time to a past time.',
                    ]);
                }

                // Check uniqueness if start time changed
                if (! $slot->starts_at->equalTo($startsAtUtc)) {
                    $conflict = $drive->slots()
                        ->where('id', '!=', $slot->id)
                        ->where('starts_at', $startsAtUtc->format('Y-m-d H:i:s'))
                        ->exists();

                    if ($conflict) {
                        throw ValidationException::withMessages([
                            'starts_at' => 'Another slot already starts at this time.',
                        ]);
                    }
                }

                $slot->starts_at = $startsAtUtc;
                $slot->ends_at = $endsAtUtc;
            }
        }

        if (isset($data['capacity'])) {
            $newCapacity = (int) $data['capacity'];

            if ($newCapacity < 1 || $newCapacity > 100) {
                throw ValidationException::withMessages([
                    'capacity' => 'Capacity must be between 1 and 100.',
                ]);
            }

            if ($newCapacity < $slot->booked_count) {
                throw ValidationException::withMessages([
                    'capacity' => "Capacity cannot be reduced below the current booked count ({$slot->booked_count}).",
                ]);
            }

            $slot->capacity = $newCapacity;
        }

        $slot->save();

        return $slot->fresh();
    }

    /**
     * Delete an existing slot.
     *
     * @throws ValidationException
     */
    public function delete(Slot $slot): void
    {
        $slot->refresh();

        if ($slot->drive->isPast()) {
            throw ValidationException::withMessages([
                'drive' => 'Cannot delete slots for past drives.',
            ]);
        }

        if ($slot->booked_count > 0) {
            throw ValidationException::withMessages([
                'booked_count' => 'Cannot delete a slot with existing bookings.',
            ]);
        }

        $slot->delete();
    }

    /**
     * Helper to resolve UTC timestamps from diverse input forms (EAT inputs).
     *
     * @param  array<string, mixed>  $data
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function resolveUtcTimestamps(array $data, ?Slot $fallback = null): array
    {
        if (isset($data['starts_at']) && isset($data['ends_at'])) {
            $startsAt = $data['starts_at'];
            $endsAt = $data['ends_at'];

            $startsAtUtc = $startsAt instanceof DateTimeInterface
                ? CarbonImmutable::instance($startsAt)->setTimezone('UTC')
                : CarbonImmutable::parse((string) $startsAt, DateHelper::TIMEZONE)->setTimezone('UTC');

            $endsAtUtc = $endsAt instanceof DateTimeInterface
                ? CarbonImmutable::instance($endsAt)->setTimezone('UTC')
                : CarbonImmutable::parse((string) $endsAt, DateHelper::TIMEZONE)->setTimezone('UTC');

            return [$startsAtUtc, $endsAtUtc];
        }

        $date = $data['date'] ?? ($fallback ? DateHelper::toEat($fallback->starts_at)?->format('Y-m-d') : null);
        $startTime = $data['start_time'] ?? ($fallback ? DateHelper::formatTime($fallback->starts_at) : null);
        $endTime = $data['end_time'] ?? ($fallback ? DateHelper::formatTime($fallback->ends_at) : null);

        if (! $date || ! $startTime || ! $endTime) {
            throw ValidationException::withMessages([
                'time' => 'Date, start time, and end time are required.',
            ]);
        }

        return [
            DateHelper::toUtc($date, $startTime),
            DateHelper::toUtc($date, $endTime),
        ];
    }

    /**
     * Validate slot date bounds, start/end ordering, and capacity.
     */
    private function validateSlotBounds(Drive $drive, CarbonImmutable $startsAtUtc, CarbonImmutable $endsAtUtc, int $capacity): void
    {
        if ($endsAtUtc->lte($startsAtUtc)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Slot end time must be after start time.',
            ]);
        }

        if ($capacity < 1 || $capacity > 100) {
            throw ValidationException::withMessages([
                'capacity' => 'Capacity must be between 1 and 100.',
            ]);
        }

        // Slot must fall within drive's dates in EAT
        $slotStartEatDate = DateHelper::toEat($startsAtUtc)?->toDateString();
        $slotEndEatDate = DateHelper::toEat($endsAtUtc)?->toDateString();

        $driveStartEat = $drive->starts_on->toDateString();
        $driveEndEat = $drive->ends_on->toDateString();

        if ($slotStartEatDate < $driveStartEat || $slotStartEatDate > $driveEndEat || $slotEndEatDate < $driveStartEat || $slotEndEatDate > $driveEndEat) {
            throw ValidationException::withMessages([
                'dates' => 'Slot must fall within the drive dates (between '.$driveStartEat.' and '.$driveEndEat.').',
            ]);
        }
    }
}
