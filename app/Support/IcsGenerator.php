<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\NotificationLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class IcsGenerator
{
    /**
     * Generate an RFC 5545 compliant iCalendar string for an appointment.
     */
    public static function generate(Appointment $appointment, ?int $sequence = null): string
    {
        $slot = $appointment->slot;
        $drive = $slot?->drive;

        $appUrl = config('app.url') ?: 'http://localhost';
        $host = parse_url((string) $appUrl, PHP_URL_HOST) ?: 'localhost';
        $uid = "appointment-{$appointment->id}@{$host}";
        $mailFromAddress = config('mail.from.address') ?: 'hello@example.com';
        $mailFromName = config('mail.from.name') ?: 'DonorLink UG';
        $dtStamp = Carbon::now('UTC')->format('Ymd\THis\Z');

        $dtStart = $slot
            ? CarbonImmutable::parse($slot->starts_at, 'UTC')->format('Ymd\THis\Z')
            : $dtStamp;

        $dtEnd = $slot
            ? CarbonImmutable::parse($slot->ends_at, 'UTC')->format('Ymd\THis\Z')
            : $dtStamp;

        $summary = 'Blood donation appointment: '.($drive?->title ?? 'UBTS Blood Drive');

        $locationParts = array_filter([$drive?->venue, $drive?->address]);
        $location = implode(', ', $locationParts) ?: 'Uganda Blood Transfusion Services';

        $description = "Please arrive a few minutes early and bring a valid photo ID (National ID, passport, driving permit, or student card). On-site UBTS staff handle reception and questions.\n\nManage your appointment: ".url('/donor/appointments');

        // Deterministic sequence: Count past and current reschedule logs for this appointment
        $seq = $sequence ?? self::resolveSequence($appointment);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Uganda Blood Transfusion Services//DonorLink UG//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$dtStamp,
            'DTSTART:'.$dtStart,
            'DTEND:'.$dtEnd,
            'SUMMARY:'.self::escapeText($summary),
            'LOCATION:'.self::escapeText($location),
            'DESCRIPTION:'.self::escapeText($description),
            'ORGANIZER;CN='.self::escapeText($mailFromName).':mailto:'.$mailFromAddress,
            'STATUS:CONFIRMED',
            'SEQUENCE:'.$seq,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $foldedLines = array_map([self::class, 'foldLine'], $lines);

        return implode("\r\n", $foldedLines)."\r\n";
    }

    /**
     * Deterministically resolve the sequence number without adding a database column.
     * Starts at 0 for initial booking, and increments by 1 for each reschedule event logged.
     */
    public static function resolveSequence(Appointment $appointment): int
    {
        return NotificationLog::query()
            ->where('related_type', Appointment::class)
            ->where('related_id', $appointment->id)
            ->where('type', 'appointment_rescheduled')
            ->whereIn('status', [NotificationStatus::Sent->value, NotificationStatus::Queued->value])
            ->count();
    }

    /**
     * Escape special characters in text properties per RFC 5545 section 3.3.11.
     */
    public static function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(["\r\n", "\n", "\r"], '\n', $text);

        return $text;
    }

    /**
     * Fold a line so no line exceeds 75 octets per RFC 5545 section 3.1.
     */
    public static function foldLine(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $result = '';
        $remaining = $line;
        $first = true;

        while (strlen($remaining) > 0) {
            $limit = $first ? 75 : 74; // Subsequent lines start with a space (1 octet)

            if (strlen($remaining) <= $limit) {
                $result .= ($first ? '' : "\r\n ").$remaining;
                break;
            }

            // Slice up to $limit bytes
            $chunk = substr($remaining, 0, $limit);
            $remaining = substr($remaining, $limit);

            $result .= ($first ? '' : "\r\n ").$chunk;
            $first = false;
        }

        return $result;
    }
}
