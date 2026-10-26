<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DonationOutcome;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\Drive;
use App\Models\User;
use App\Support\DateHelper;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    /**
     * Sanitize a single cell value against CSV formula injection.
     * Prefixes with a single quote if the first character is =, +, -, @, tab, or CR.
     */
    public static function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        if ($str !== '') {
            $firstChar = $str[0];
            if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
                $str = "'".$str;
            }
        }

        return $str;
    }

    /**
     * Format a phone number in local grouped format (e.g. 0700 000 003).
     * Retains leading zero and is treated as text by spreadsheet software without quote prefix.
     */
    public static function formatLocalPhone(?string $phone): string
    {
        if ($phone === null || trim($phone) === '') {
            return 'N/A';
        }

        $digits = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($digits, '256') && strlen($digits) === 12) {
            $local = '0'.substr($digits, 3);
        } elseif (strlen($digits) === 9) {
            $local = '0'.$digits;
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $local = $digits;
        } else {
            return $phone;
        }

        return substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7);
    }

    /**
     * Stream CSV with UTF-8 BOM, sanitized rows, and custom headers.
     *
     * @param  \Closure(resource): int  $callback  Receives output stream and returns total row count
     */
    protected function streamCsv(string $filename, \Closure $callback): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($callback) {
            $handle = fopen('php://output', 'wb');

            // UTF-8 Byte Order Mark (BOM) for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            $callback($handle);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Drive Attendance Roster CSV (Staff, Admin).
     * Columns: Slot time, donor name, phone, email, status, checked-in time, outcome.
     */
    public function exportDriveRoster(Drive $drive, User $actor): StreamedResponse
    {
        $todayEatDate = DateHelper::today()->toDateString();
        $filename = "donorlink-drive-{$drive->id}-roster-{$todayEatDate}.csv";

        return $this->streamCsv($filename, function ($handle) use ($drive, $actor) {
            // Header row
            fputcsv($handle, [
                'Slot Time (EAT)',
                'Donor Name',
                'Phone',
                'Email',
                'Booking Status',
                'Checked-In Time (EAT)',
                'Outcome',
            ]);

            $rowCount = 0;

            // Fetch appointments with relations
            $appointments = Appointment::query()
                ->whereHas('slot', fn ($q) => $q->where('drive_id', $drive->id))
                ->with(['slot', 'user', 'donationRecord'])
                ->join('slots', 'appointments.slot_id', '=', 'slots.id')
                ->orderBy('slots.starts_at')
                ->orderBy('appointments.id')
                ->select('appointments.*')
                ->cursor();

            foreach ($appointments as $appointment) {
                $slot = $appointment->slot;
                $user = $appointment->user;
                $donation = $appointment->donationRecord;

                $slotTimeEat = $slot
                    ? Carbon::parse($slot->starts_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i').' – '.Carbon::parse($slot->ends_at)->setTimezone('Africa/Kampala')->format('H:i')
                    : 'N/A';

                $checkedInEat = $appointment->checked_in_at
                    ? Carbon::parse($appointment->checked_in_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i:s')
                    : 'Not checked in';

                $outcomeText = match ($donation?->outcome) {
                    DonationOutcome::Donated => 'Donated',
                    DonationOutcome::Deferred => 'Did not donate',
                    null => 'No outcome recorded',
                };

                fputcsv($handle, [
                    $slotTimeEat,
                    self::sanitizeCell($user->name),
                    self::formatLocalPhone($user->phone),
                    self::sanitizeCell($user->email),
                    $appointment->status->label(),
                    $checkedInEat,
                    $outcomeText,
                ]);

                $rowCount++;
            }

            // Also include walk-in donation records (no appointment)
            $walkIns = DonationRecord::query()
                ->where('drive_id', $drive->id)
                ->whereNull('appointment_id')
                ->with('user')
                ->cursor();

            foreach ($walkIns as $walkIn) {
                $user = $walkIn->user;
                $recordedEat = Carbon::parse($walkIn->recorded_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i:s');
                $outcomeText = $walkIn->outcome === DonationOutcome::Donated ? 'Donated' : 'Did not donate';

                fputcsv($handle, [
                    'Walk-in (No Slot)',
                    self::sanitizeCell($user->name),
                    self::formatLocalPhone($user->phone),
                    self::sanitizeCell($user->email),
                    'Walk-in',
                    $recordedEat,
                    $outcomeText,
                ]);

                $rowCount++;
            }

            // Audit log export creation
            AuditLogger::record($actor, 'export.created', $drive, [
                'export_type' => 'drive_roster',
                'drive_id' => $drive->id,
                'row_count' => $rowCount,
            ]);

            return $rowCount;
        });
    }

    /**
     * Export Drive Summary CSV (Staff, Admin).
     * Includes Funnel and By-Slot breakdown tables.
     */
    public function exportDriveSummary(Drive $drive, User $actor): StreamedResponse
    {
        $metrics = $this->reportService->getDriveMetrics($drive);
        $todayEatDate = DateHelper::today()->toDateString();
        $filename = "donorlink-drive-{$drive->id}-summary-{$todayEatDate}.csv";

        return $this->streamCsv($filename, function ($handle) use ($drive, $metrics, $actor) {
            $rowCount = 0;

            // Section 1: Drive Details
            fputcsv($handle, ['DRIVE SUMMARY REPORT', $drive->title]);
            fputcsv($handle, ['Venue', self::sanitizeCell($drive->venue)]);
            fputcsv($handle, ['Dates (EAT)', self::sanitizeCell($drive->starts_on->format('Y-m-d').' to '.$drive->ends_on->format('Y-m-d'))]);
            fputcsv($handle, ['Status', self::sanitizeCell($drive->status->label())]);
            fputcsv($handle, []);
            $rowCount += 5;

            // Section 2: Funnel Table
            fputcsv($handle, ['FUNNEL METRICS', 'COUNT', 'RATE']);
            fputcsv($handle, ['Total Capacity', $metrics['capacity'], '—']);
            fputcsv($handle, ['Booked (All Live)', $metrics['booked'], ReportService::formatRate($metrics['fill_rate']).' (Fill rate)']);
            fputcsv($handle, ['Cancelled', $metrics['cancelled'], '—']);
            fputcsv($handle, ['Checked In', $metrics['checked_in'], '—']);
            fputcsv($handle, ['Attended', $metrics['attended'], ReportService::formatRate($metrics['attendance_rate']).' (Attendance rate)']);
            fputcsv($handle, ['Donated', $metrics['donated'], ReportService::formatRate($metrics['donation_rate']).' (Donation rate)']);
            fputcsv($handle, ['Did Not Donate', $metrics['deferred'], '—']);
            fputcsv($handle, ['No-Show', $metrics['no_show'], '—']);
            fputcsv($handle, ['Walk-Ins', $metrics['walk_ins'], '— (Counted separately)']);
            fputcsv($handle, []);
            $rowCount += 11;

            // Section 3: By-Slot Table
            fputcsv($handle, ['BY-SLOT PERFORMANCE']);
            fputcsv($handle, ['Slot Time (EAT)', 'Capacity', 'Booked', 'Attended', 'No-Show', 'Fill Rate']);
            $rowCount += 2;

            foreach ($metrics['by_slot'] as $slotRow) {
                $slotFill = $slotRow['capacity'] > 0 ? round(($slotRow['booked'] / $slotRow['capacity']) * 100, 1).'%' : 'No data';
                fputcsv($handle, [
                    self::sanitizeCell($slotRow['label']),
                    $slotRow['capacity'],
                    $slotRow['booked'],
                    $slotRow['attended'],
                    $slotRow['no_show'],
                    $slotFill,
                ]);
                $rowCount++;
            }

            AuditLogger::record($actor, 'export.created', $drive, [
                'export_type' => 'drive_summary',
                'drive_id' => $drive->id,
                'row_count' => $rowCount,
            ]);

            return $rowCount;
        });
    }

    /**
     * Export Period Summary CSV (Staff, Admin).
     * Rows per drive held in the period.
     */
    public function exportPeriodSummary(CarbonInterface $startEat, CarbonInterface $endEat, User $actor): StreamedResponse
    {
        $metrics = $this->reportService->getPeriodMetrics($startEat, $endEat);
        $startStr = $metrics['start_eat']->format('Y-m-d');
        $endStr = $metrics['end_eat']->format('Y-m-d');
        $filename = "donorlink-period-summary-{$startStr}-to-{$endStr}.csv";

        return $this->streamCsv($filename, function ($handle) use ($metrics, $startStr, $endStr, $actor) {
            fputcsv($handle, [
                'Drive Title',
                'Venue',
                'Starts On (EAT)',
                'Ends On (EAT)',
                'Status',
                'Capacity',
                'Booked',
                'Attended',
                'Donated',
                'Did Not Donate',
                'No-Show',
                'Walk-Ins',
                'Fill Rate',
                'Attendance Rate',
            ]);

            $rowCount = 0;

            foreach ($metrics['per_drive'] as $driveMetrics) {
                $drive = $driveMetrics['drive'];
                fputcsv($handle, [
                    self::sanitizeCell($drive->title),
                    self::sanitizeCell($drive->venue),
                    $drive->starts_on->format('Y-m-d'),
                    $drive->ends_on->format('Y-m-d'),
                    $drive->status->label(),
                    $driveMetrics['capacity'],
                    $driveMetrics['booked'],
                    $driveMetrics['attended'],
                    $driveMetrics['donated'],
                    $driveMetrics['deferred'],
                    $driveMetrics['no_show'],
                    $driveMetrics['walk_ins'],
                    ReportService::formatRate($driveMetrics['fill_rate']),
                    ReportService::formatRate($driveMetrics['attendance_rate']),
                ]);
                $rowCount++;
            }

            // Period Summary Footer Totals
            fputcsv($handle, [
                'PERIOD TOTALS',
                "— ({$metrics['drives_held']} drives held)",
                $startStr,
                $endStr,
                '—',
                $metrics['capacity'],
                $metrics['booked'],
                $metrics['attended'],
                $metrics['donated'],
                $metrics['deferred'],
                $metrics['no_show'],
                $metrics['walk_ins'],
                '—',
                ReportService::formatRate($metrics['attendance_rate']),
            ]);

            AuditLogger::record($actor, 'export.created', null, [
                'export_type' => 'period_summary',
                'start_date' => $startStr,
                'end_date' => $endStr,
                'row_count' => $rowCount,
            ]);

            return $rowCount;
        });
    }

    /**
     * Export Donation Records CSV (Admin Only).
     * Columns: Donor name, drive, date (EAT), outcome.
     * Strictly excludes notes, blood group, date of birth, or medical data.
     */
    public function exportDonationRecords(CarbonInterface $startEat, CarbonInterface $endEat, User $actor): StreamedResponse
    {
        $boundaries = $this->reportService->parseEatDateRange($startEat, $endEat);
        $startStr = $boundaries['startEat']->format('Y-m-d');
        $endStr = $boundaries['endEat']->format('Y-m-d');
        $filename = "donorlink-donation-records-{$startStr}-to-{$endStr}.csv";

        return $this->streamCsv($filename, function ($handle) use ($boundaries, $startStr, $endStr, $actor) {
            fputcsv($handle, [
                'Donor Name',
                'Blood Drive',
                'Date Recorded (EAT)',
                'Outcome',
                'Type',
            ]);

            $rowCount = 0;

            $records = DonationRecord::query()
                ->whereBetween('recorded_at', [$boundaries['startUtc'], $boundaries['endUtc']])
                ->with(['user', 'drive'])
                ->orderByDesc('recorded_at')
                ->cursor();

            foreach ($records as $record) {
                $recordedEat = Carbon::parse($record->recorded_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i:s');
                $outcomeText = $record->outcome === DonationOutcome::Donated ? 'Donated' : 'Did not donate';
                $type = $record->appointment_id !== null ? 'Appointment' : 'Walk-in';

                fputcsv($handle, [
                    self::sanitizeCell($record->user->name),
                    self::sanitizeCell($record->drive->title),
                    self::sanitizeCell($recordedEat),
                    self::sanitizeCell($outcomeText),
                    self::sanitizeCell($type),
                ]);

                $rowCount++;
            }

            AuditLogger::record($actor, 'export.created', null, [
                'export_type' => 'donation_records',
                'start_date' => $startStr,
                'end_date' => $endStr,
                'row_count' => $rowCount,
            ]);

            return $rowCount;
        });
    }
}
