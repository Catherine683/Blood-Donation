<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Drive;
use App\Models\Slot;
use App\Support\DateHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SlotGenerator
{
    public const ALLOWED_LENGTHS = [10, 15, 20, 30, 45, 60];

    public const MAX_SLOTS_PER_RUN = 200;

    /**
     * Preview slot generation without saving to the database.
     *
     * @param  array{
     *     dates: array<int, string>|string,
     *     start_time: string,
     *     end_time: string,
     *     slot_length: int|string,
     *     capacity: int|string,
     *     break_start_time?: string|null,
     *     break_end_time?: string|null,
     * }  $options
     * @return array{
     *     total_to_create: int,
     *     total_skipped: int,
     *     skipped_collisions: int,
     *     skipped_breaks: int,
     *     skipped_past: int,
     *     slots: array<int, array<string, mixed>>,
     * }
     *
     * @throws ValidationException
     */
    public function preview(Drive $drive, array $options): array
    {
        $this->validateOptions($drive, $options);

        $dates = is_array($options['dates']) ? $options['dates'] : [$options['dates']];
        sort($dates);

        $startTimeStr = trim($options['start_time']);
        $endTimeStr = trim($options['end_time']);
        $slotLength = (int) $options['slot_length'];
        $capacity = (int) $options['capacity'];

        $hasBreak = ! empty($options['break_start_time']) && ! empty($options['break_end_time']);
        $breakStartStr = $hasBreak ? trim((string) $options['break_start_time']) : null;
        $breakEndStr = $hasBreak ? trim((string) $options['break_end_time']) : null;

        // Fetch existing slots for collision detection directly from database as raw UTC strings
        $existingStarts = DB::table('slots')
            ->where('drive_id', $drive->id)
            ->pluck('starts_at')
            ->map(fn ($dt) => CarbonImmutable::parse((string) $dt, 'UTC')->format('Y-m-d H:i:s'))
            ->flip()
            ->toArray();

        $candidateSlots = [];
        $skippedCollisions = 0;
        $skippedBreaks = 0;
        $skippedPast = 0;

        $nowUtc = Carbon::now('UTC');

        foreach ($dates as $date) {
            $dayStartEat = CarbonImmutable::parse("$date $startTimeStr", DateHelper::TIMEZONE);
            $dayEndEat = CarbonImmutable::parse("$date $endTimeStr", DateHelper::TIMEZONE);

            $breakStartEat = ($hasBreak && $breakStartStr) ? CarbonImmutable::parse("$date $breakStartStr", DateHelper::TIMEZONE) : null;
            $breakEndEat = ($hasBreak && $breakEndStr) ? CarbonImmutable::parse("$date $breakEndStr", DateHelper::TIMEZONE) : null;

            $currentSlotStart = $dayStartEat;

            while ($currentSlotStart->addMinutes($slotLength)->lte($dayEndEat)) {
                $currentSlotEnd = $currentSlotStart->addMinutes($slotLength);

                // Break overlap check: slot [start, end] overlaps break [bStart, bEnd] if start < bEnd && end > bStart
                if ($hasBreak && $breakStartEat && $breakEndEat) {
                    if ($currentSlotStart->lt($breakEndEat) && $currentSlotEnd->gt($breakStartEat)) {
                        $skippedBreaks++;
                        $currentSlotStart = $currentSlotEnd;

                        continue;
                    }
                }

                $startsAtUtc = $currentSlotStart->setTimezone('UTC');
                $endsAtUtc = $currentSlotEnd->setTimezone('UTC');

                // Past slot check
                if ($startsAtUtc->lte($nowUtc)) {
                    $skippedPast++;
                    $currentSlotStart = $currentSlotEnd;

                    continue;
                }

                // Collision check with existing slots
                $utcKey = $startsAtUtc->format('Y-m-d H:i:s');
                if (isset($existingStarts[$utcKey])) {
                    $skippedCollisions++;
                    $currentSlotStart = $currentSlotEnd;

                    continue;
                }

                $candidateSlots[] = [
                    'date' => $date,
                    'formatted_date' => DateHelper::formatDate($currentSlotStart),
                    'start_time' => $currentSlotStart->format('H:i'),
                    'end_time' => $currentSlotEnd->format('H:i'),
                    'starts_at_utc' => $startsAtUtc->toIso8601String(),
                    'ends_at_utc' => $endsAtUtc->toIso8601String(),
                    'starts_at_utc_db' => $startsAtUtc->format('Y-m-d H:i:s'),
                    'ends_at_utc_db' => $endsAtUtc->format('Y-m-d H:i:s'),
                    'capacity' => $capacity,
                    'display_range' => "{$currentSlotStart->format('H:i')} – {$currentSlotEnd->format('H:i')} EAT",
                ];

                $currentSlotStart = $currentSlotEnd;
            }
        }

        if (count($candidateSlots) > self::MAX_SLOTS_PER_RUN) {
            throw ValidationException::withMessages([
                'slots' => 'Generation run would create '.count($candidateSlots).' slots, exceeding the maximum cap of '.self::MAX_SLOTS_PER_RUN.' slots.',
            ]);
        }

        $totalSkipped = $skippedCollisions + $skippedBreaks + $skippedPast;

        return [
            'total_to_create' => count($candidateSlots),
            'total_skipped' => $totalSkipped,
            'skipped_collisions' => $skippedCollisions,
            'skipped_breaks' => $skippedBreaks,
            'skipped_past' => $skippedPast,
            'slots' => $candidateSlots,
        ];
    }

    /**
     * Generate and persist slots in a single transaction.
     *
     * @param  array{
     *     dates: array<int, string>|string,
     *     start_time: string,
     *     end_time: string,
     *     slot_length: int|string,
     *     capacity: int|string,
     *     break_start_time?: string|null,
     *     break_end_time?: string|null,
     * }  $options
     * @return array{
     *     created: int,
     *     skipped: int,
     *     skipped_collisions: int,
     *     skipped_breaks: int,
     *     skipped_past: int,
     * }
     *
     * @throws ValidationException
     */
    public function generate(Drive $drive, array $options): array
    {
        $preview = $this->preview($drive, $options);

        return DB::transaction(function () use ($drive, $preview) {
            $now = Carbon::now('UTC');
            $rows = [];

            foreach ($preview['slots'] as $slotData) {
                $rows[] = [
                    'drive_id' => $drive->id,
                    'starts_at' => $slotData['starts_at_utc_db'],
                    'ends_at' => $slotData['ends_at_utc_db'],
                    'capacity' => $slotData['capacity'],
                    'booked_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                Slot::insert($rows);
            }

            return [
                'created' => count($rows),
                'skipped' => $preview['total_skipped'],
                'skipped_collisions' => $preview['skipped_collisions'],
                'skipped_breaks' => $preview['skipped_breaks'],
                'skipped_past' => $preview['skipped_past'],
            ];
        });
    }

    /**
     * Validate common generator options.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws ValidationException
     */
    private function validateOptions(Drive $drive, array $options): void
    {
        if ($drive->isPast()) {
            throw ValidationException::withMessages([
                'drive' => 'Cannot generate slots for past drives.',
            ]);
        }

        $dates = $options['dates'] ?? null;
        if (empty($dates)) {
            throw ValidationException::withMessages([
                'dates' => 'At least one date is required.',
            ]);
        }

        $dateList = is_array($dates) ? $dates : [$dates];
        $driveStartEat = $drive->starts_on->toDateString();
        $driveEndEat = $drive->ends_on->toDateString();

        foreach ($dateList as $date) {
            if ($date < $driveStartEat || $date > $driveEndEat) {
                throw ValidationException::withMessages([
                    'dates' => "Date {$date} must fall within the drive dates ({$driveStartEat} to {$driveEndEat}).",
                ]);
            }
        }

        $startTimeStr = $options['start_time'] ?? null;
        $endTimeStr = $options['end_time'] ?? null;

        if (! $startTimeStr || ! $endTimeStr) {
            throw ValidationException::withMessages([
                'time' => 'Start time and end time are required.',
            ]);
        }

        if ($startTimeStr >= $endTimeStr) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $slotLength = (int) ($options['slot_length'] ?? 0);
        if (! in_array($slotLength, self::ALLOWED_LENGTHS, true)) {
            throw ValidationException::withMessages([
                'slot_length' => 'Slot length must be 10, 15, 20, 30, 45, or 60 minutes.',
            ]);
        }

        $capacity = (int) ($options['capacity'] ?? 0);
        if ($capacity < 1 || $capacity > 100) {
            throw ValidationException::withMessages([
                'capacity' => 'Capacity per slot must be between 1 and 100.',
            ]);
        }

        $hasBreakStart = ! empty($options['break_start_time']);
        $hasBreakEnd = ! empty($options['break_end_time']);

        if ($hasBreakStart xor $hasBreakEnd) {
            throw ValidationException::withMessages([
                'break' => 'Both break start time and break end time must be provided.',
            ]);
        }

        if ($hasBreakStart && $hasBreakEnd) {
            if ($options['break_start_time'] >= $options['break_end_time']) {
                throw ValidationException::withMessages([
                    'break_end_time' => 'Break end time must be after break start time.',
                ]);
            }
        }
    }
}
