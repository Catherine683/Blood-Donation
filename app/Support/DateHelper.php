<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class DateHelper
{
    public const TIMEZONE = 'Africa/Kampala';

    /**
     * Get the current date in EAT (Africa/Kampala).
     */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Parse a date and time string in EAT and convert to UTC for storage.
     */
    public static function toUtc(string|DateTimeInterface $date, ?string $time = null): CarbonImmutable
    {
        if ($date instanceof DateTimeInterface && $time === null) {
            return CarbonImmutable::instance($date)->setTimezone('UTC');
        }

        $dateString = is_string($date) ? $date : $date->format('Y-m-d');
        $timeString = $time ? trim($time) : '00:00:00';

        // Ensure seconds are included if not present
        if (substr_count($timeString, ':') === 1) {
            $timeString .= ':00';
        }

        return CarbonImmutable::parse("$dateString $timeString", self::TIMEZONE)->setTimezone('UTC');
    }

    /**
     * Convert any stored UTC date/time to EAT for display.
     */
    public static function toEat(DateTimeInterface|string|null $datetime): ?CarbonImmutable
    {
        if ($datetime === null) {
            return null;
        }

        if (is_string($datetime)) {
            return CarbonImmutable::parse($datetime, 'UTC')->setTimezone(self::TIMEZONE);
        }

        return CarbonImmutable::instance($datetime)->setTimezone(self::TIMEZONE);
    }

    /**
     * Format a stored UTC timestamp for human display in EAT.
     * Example: 'D, d M Y' -> 'Mon, 20 Oct 2026'
     */
    public static function formatDate(DateTimeInterface|string|null $datetime, string $format = 'D, d M Y'): string
    {
        $eat = self::toEat($datetime);

        return $eat ? $eat->format($format) : '';
    }

    /**
     * Format a stored UTC timestamp to 24h EAT time.
     * Example: '09:00'
     */
    public static function formatTime(DateTimeInterface|string|null $datetime, string $format = 'H:i'): string
    {
        $eat = self::toEat($datetime);

        return $eat ? $eat->format($format) : '';
    }

    /**
     * Format a slot range in EAT.
     * Example: '09:00 – 09:30 EAT'
     */
    public static function formatRange(DateTimeInterface|string $startsAt, DateTimeInterface|string $endsAt): string
    {
        $start = self::formatTime($startsAt);
        $end = self::formatTime($endsAt);

        return "{$start} – {$end} EAT";
    }

    /**
     * Format a date range for a drive in EAT.
     * Example: '20 Oct 2026' or '20 Oct – 22 Oct 2026'
     */
    public static function formatDateRange(DateTimeInterface|string $startsOn, DateTimeInterface|string $endsOn): string
    {
        $start = is_string($startsOn) ? CarbonImmutable::parse($startsOn) : CarbonImmutable::instance($startsOn);
        $end = is_string($endsOn) ? CarbonImmutable::parse($endsOn) : CarbonImmutable::instance($endsOn);

        if ($start->toDateString() === $end->toDateString()) {
            return $start->format('d M Y');
        }

        if ($start->year === $end->year) {
            return $start->format('d M').' – '.$end->format('d M Y');
        }

        return $start->format('d M Y').' – '.$end->format('d M Y');
    }
}
