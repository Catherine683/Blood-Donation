<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Support\DateHelper;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Parse date range in EAT (Africa/Kampala) and convert to exact UTC boundaries.
     *
     * @return array{
     *     startEat: Carbon,
     *     endEat: Carbon,
     *     startUtc: Carbon,
     *     endUtc: Carbon
     * }
     */
    public function parseEatDateRange(CarbonInterface|string $start, CarbonInterface|string $end): array
    {
        $startEat = is_string($start)
            ? Carbon::parse($start, 'Africa/Kampala')->startOfDay()
            : Carbon::instance($start)->setTimezone('Africa/Kampala')->startOfDay();

        $endEat = is_string($end)
            ? Carbon::parse($end, 'Africa/Kampala')->endOfDay()
            : Carbon::instance($end)->setTimezone('Africa/Kampala')->endOfDay();

        $startUtc = $startEat->copy()->setTimezone('UTC');
        $endUtc = $endEat->copy()->setTimezone('UTC');

        return [
            'startEat' => $startEat,
            'endEat' => $endEat,
            'startUtc' => $startUtc,
            'endUtc' => $endUtc,
        ];
    }

    /**
     * Format a rate as a percentage string with one decimal place or "No data" if denominator is zero.
     */
    public static function formatRate(?float $rate): string
    {
        if ($rate === null) {
            return 'No data';
        }

        return number_format($rate * 100, 1).'%';
    }

    /**
     * Compute comprehensive metrics for a single blood drive.
     * Guaranteed to reconcile exactly with the Phase 5 roster summary.
     *
     * @return array{
     *     drive: Drive,
     *     capacity: int,
     *     booked: int,
     *     expected: int,
     *     cancelled: int,
     *     checked_in: int,
     *     attended: int,
     *     no_show: int,
     *     donated: int,
     *     deferred: int,
     *     walk_ins: int,
     *     waiting_to_arrive: int,
     *     awaiting_outcome: int,
     *     has_unrecorded: bool,
     *     unrecorded_count: int,
     *     fill_rate: float|null,
     *     attendance_rate: float|null,
     *     donation_rate: float|null,
     *     by_slot: Collection<int, array<string, mixed>>,
     *     by_hour: array<int, array{hour: int, label: string, count: int}>
     * }
     */
    public function getDriveMetrics(Drive $drive): array
    {
        $slots = $drive->slots()->get();
        $slotIds = $slots->pluck('id');

        $capacity = (int) $slots->sum('capacity');

        // Fast SQL aggregate on appointments for this drive
        $apptAgg = Appointment::query()
            ->whereIn('slot_id', $slotIds)
            ->selectRaw("
                COUNT(CASE WHEN status IN ('booked', 'attended', 'no_show') THEN 1 END) as booked_count,
                COUNT(CASE WHEN status IN ('booked', 'attended') THEN 1 END) as expected_count,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN checked_in_at IS NOT NULL THEN 1 END) as checked_in_count,
                COUNT(CASE WHEN status = 'attended' THEN 1 END) as attended_count,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_count,
                COUNT(CASE WHEN status = 'booked' THEN 1 END) as unrecorded_count,
                COUNT(CASE WHEN status = 'booked' AND checked_in_at IS NULL THEN 1 END) as waiting_to_arrive_count,
                COUNT(CASE WHEN status = 'booked' AND checked_in_at IS NOT NULL THEN 1 END) as awaiting_outcome_count
            ")
            ->first();

        $booked = (int) ($apptAgg->booked_count ?? 0);
        $expected = (int) ($apptAgg->expected_count ?? 0);
        $cancelled = (int) ($apptAgg->cancelled_count ?? 0);
        $checkedIn = (int) ($apptAgg->checked_in_count ?? 0);
        $attended = (int) ($apptAgg->attended_count ?? 0);
        $noShow = (int) ($apptAgg->no_show_count ?? 0);
        $unrecordedCount = (int) ($apptAgg->unrecorded_count ?? 0);
        $waitingToArrive = (int) ($apptAgg->waiting_to_arrive_count ?? 0);
        $awaitingOutcome = (int) ($apptAgg->awaiting_outcome_count ?? 0);

        // Outcomes from donation_records
        $outcomeAgg = DonationRecord::query()
            ->where('drive_id', $drive->id)
            ->selectRaw("
                COUNT(CASE WHEN outcome = 'donated' THEN 1 END) as donated_count,
                COUNT(CASE WHEN outcome = 'deferred' THEN 1 END) as deferred_count,
                COUNT(CASE WHEN appointment_id IS NULL THEN 1 END) as walk_in_count
            ")
            ->first();

        $donated = (int) ($outcomeAgg->donated_count ?? 0);
        $deferred = (int) ($outcomeAgg->deferred_count ?? 0);
        $walkIns = (int) ($outcomeAgg->walk_in_count ?? 0);

        // Check if drive has any unrecorded ended appointments
        $nowUtc = Carbon::now('UTC');
        $hasUnrecorded = Appointment::query()
            ->whereIn('slot_id', $slotIds)
            ->where('status', AppointmentStatus::Booked)
            ->whereHas('slot', fn ($q) => $q->where('ends_at', '<=', $nowUtc))
            ->exists();

        // Calculate rates with zero-denominator safety
        $fillRate = $capacity > 0 ? (float) ($booked / $capacity) : null;

        // Attendance rate: attended ÷ (attended + no_show) over ended appointments
        $attendanceDenominator = $attended + $noShow;
        $attendanceRate = $attendanceDenominator > 0 ? (float) ($attended / $attendanceDenominator) : null;

        // Donation rate: donated ÷ recorded outcomes (donated + did_not_donate), including walk-ins
        $totalOutcomes = $donated + $deferred;
        $donationRate = $totalOutcomes > 0 ? (float) ($donated / $totalOutcomes) : null;

        // By-slot breakdown
        $slotAppointments = Appointment::query()
            ->whereIn('slot_id', $slotIds)
            ->selectRaw("
                slot_id,
                COUNT(CASE WHEN status IN ('booked', 'attended', 'no_show') THEN 1 END) as booked,
                COUNT(CASE WHEN status = 'attended' THEN 1 END) as attended,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show
            ")
            ->groupBy('slot_id')
            ->get()
            ->keyBy('slot_id');

        $bySlot = $slots->map(function (Slot $slot) use ($slotAppointments) {
            $agg = $slotAppointments->get($slot->id);
            $startEat = Carbon::parse($slot->starts_at)->setTimezone('Africa/Kampala');
            $endEat = Carbon::parse($slot->ends_at)->setTimezone('Africa/Kampala');

            return [
                'slot_id' => $slot->id,
                'label' => $startEat->format('H:i').' – '.$endEat->format('H:i'),
                'starts_at_eat' => $startEat->format('Y-m-d H:i'),
                'time_range_eat' => $startEat->format('H:i').' – '.$endEat->format('H:i'),
                'capacity' => (int) $slot->capacity,
                'booked' => (int) ($agg->booked ?? 0),
                'attended' => (int) ($agg->attended ?? 0),
                'no_show' => (int) ($agg->no_show ?? 0),
            ];
        });

        // By-hour distribution: bookings per hour in EAT (08:00 to 18:00)
        $hourlyBuckets = [];
        for ($h = 8; $h <= 18; $h++) {
            $hourlyBuckets[$h] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'count' => 0,
            ];
        }

        foreach ($slots as $slot) {
            $slotHour = (int) Carbon::parse($slot->starts_at)->setTimezone('Africa/Kampala')->format('H');
            $bookedForSlot = (int) ($slotAppointments->get($slot->id)->booked ?? 0);
            if (isset($hourlyBuckets[$slotHour])) {
                $hourlyBuckets[$slotHour]['count'] += $bookedForSlot;
            }
        }

        return [
            'drive' => $drive,
            'capacity' => $capacity,
            'booked' => $booked,
            'expected' => $expected,
            'cancelled' => $cancelled,
            'checked_in' => $checkedIn,
            'attended' => $attended,
            'no_show' => $noShow,
            'donated' => $donated,
            'deferred' => $deferred,
            'walk_ins' => $walkIns,
            'waiting_to_arrive' => $waitingToArrive,
            'awaiting_outcome' => $awaitingOutcome,
            'has_unrecorded' => $hasUnrecorded,
            'unrecorded_count' => $unrecordedCount,
            'fill_rate' => $fillRate,
            'attendance_rate' => $attendanceRate,
            'donation_rate' => $donationRate,
            'by_slot' => $bySlot,
            'by_hour' => array_values($hourlyBuckets),
        ];
    }

    /**
     * Compute aggregated operational metrics across a date period (EAT).
     * Fully batch-aggregated so query count is flat and minimal regardless of drive count.
     *
     * @return array{
     *     start_eat: Carbon,
     *     end_eat: Carbon,
     *     drives_held: int,
     *     capacity: int,
     *     booked: int,
     *     attended: int,
     *     no_show: int,
     *     donated: int,
     *     deferred: int,
     *     walk_ins: int,
     *     first_time_donors: int,
     *     new_registrations: int,
     *     attendance_rate: float|null,
     *     donation_rate: float|null,
     *     per_drive: Collection<int, array<string, mixed>>,
     *     turnout_by_drive: array<int, array{id: int, title: string, expected: int, attended: int, donated: int}>,
     *     bookings_by_week: array<int, array{week_label: string, count: int}>
     * }
     */
    public function getPeriodMetrics(CarbonInterface $startEat, CarbonInterface $endEat): array
    {
        $boundaries = $this->parseEatDateRange($startEat, $endEat);
        $startEatDate = $boundaries['startEat']->toDateString();
        $endEatDate = $boundaries['endEat']->toDateString();
        $startUtc = $boundaries['startUtc'];
        $endUtc = $boundaries['endUtc'];

        // Drives held within this period (ends_on within period, published or closed)
        $drives = Drive::query()
            ->whereIn('status', [DriveStatus::Published, DriveStatus::Closed])
            ->whereDate('ends_on', '>=', $startEatDate)
            ->whereDate('ends_on', '<=', $endEatDate)
            ->orderBy('starts_on')
            ->get();

        $drivesHeld = $drives->count();
        $driveIds = $drives->pluck('id');

        // BATCH QUERY 1: Slots by drive
        $slotsByDrive = Slot::query()
            ->whereIn('drive_id', $driveIds)
            ->get()
            ->groupBy('drive_id');

        // BATCH QUERY 2: Appointments aggregated by drive
        $apptsByDrive = Appointment::query()
            ->join('slots', 'appointments.slot_id', '=', 'slots.id')
            ->whereIn('slots.drive_id', $driveIds)
            ->selectRaw("
                slots.drive_id,
                COUNT(CASE WHEN appointments.status IN ('booked', 'attended', 'no_show') THEN 1 END) as booked_count,
                COUNT(CASE WHEN appointments.status IN ('booked', 'attended') THEN 1 END) as expected_count,
                COUNT(CASE WHEN appointments.status = 'cancelled' THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN appointments.checked_in_at IS NOT NULL THEN 1 END) as checked_in_count,
                COUNT(CASE WHEN appointments.status = 'attended' THEN 1 END) as attended_count,
                COUNT(CASE WHEN appointments.status = 'no_show' THEN 1 END) as no_show_count,
                COUNT(CASE WHEN appointments.status = 'booked' THEN 1 END) as unrecorded_count,
                COUNT(CASE WHEN appointments.status = 'booked' AND appointments.checked_in_at IS NULL THEN 1 END) as waiting_to_arrive_count,
                COUNT(CASE WHEN appointments.status = 'booked' AND appointments.checked_in_at IS NOT NULL THEN 1 END) as awaiting_outcome_count
            ")
            ->groupBy('slots.drive_id')
            ->get()
            ->keyBy('drive_id');

        // BATCH QUERY 3: Outcomes aggregated by drive
        $outcomesByDrive = DonationRecord::query()
            ->whereIn('drive_id', $driveIds)
            ->selectRaw("
                drive_id,
                COUNT(CASE WHEN outcome = 'donated' THEN 1 END) as donated_count,
                COUNT(CASE WHEN outcome = 'deferred' THEN 1 END) as deferred_count,
                COUNT(CASE WHEN appointment_id IS NULL THEN 1 END) as walk_in_count
            ")
            ->groupBy('drive_id')
            ->get()
            ->keyBy('drive_id');

        // Build per-drive metrics in memory with zero extra queries
        $nowUtc = Carbon::now('UTC');
        $perDrive = $drives->map(function (Drive $drive) use ($slotsByDrive, $apptsByDrive, $outcomesByDrive) {
            $slots = $slotsByDrive->get($drive->id, collect());
            $capacity = (int) $slots->sum('capacity');

            $apptAgg = $apptsByDrive->get($drive->id);
            $booked = (int) ($apptAgg?->booked_count ?? 0);
            $expected = (int) ($apptAgg?->expected_count ?? 0);
            $cancelled = (int) ($apptAgg?->cancelled_count ?? 0);
            $checkedIn = (int) ($apptAgg?->checked_in_count ?? 0);
            $attended = (int) ($apptAgg?->attended_count ?? 0);
            $noShow = (int) ($apptAgg?->no_show_count ?? 0);
            $unrecordedCount = (int) ($apptAgg?->unrecorded_count ?? 0);
            $waitingToArrive = (int) ($apptAgg?->waiting_to_arrive_count ?? 0);
            $awaitingOutcome = (int) ($apptAgg?->awaiting_outcome_count ?? 0);

            $outcomeAgg = $outcomesByDrive->get($drive->id);
            $donated = (int) ($outcomeAgg?->donated_count ?? 0);
            $deferred = (int) ($outcomeAgg?->deferred_count ?? 0);
            $walkIns = (int) ($outcomeAgg?->walk_in_count ?? 0);

            $fillRate = $capacity > 0 ? (float) ($booked / $capacity) : null;
            $attendanceDenom = $attended + $noShow;
            $attendanceRate = $attendanceDenom > 0 ? (float) ($attended / $attendanceDenom) : null;
            $totalOutcomes = $donated + $deferred;
            $donationRate = $totalOutcomes > 0 ? (float) ($donated / $totalOutcomes) : null;

            return [
                'drive' => $drive,
                'capacity' => $capacity,
                'booked' => $booked,
                'expected' => $expected,
                'cancelled' => $cancelled,
                'checked_in' => $checkedIn,
                'attended' => $attended,
                'no_show' => $noShow,
                'donated' => $donated,
                'deferred' => $deferred,
                'walk_ins' => $walkIns,
                'waiting_to_arrive' => $waitingToArrive,
                'awaiting_outcome' => $awaitingOutcome,
                'fill_rate' => $fillRate,
                'attendance_rate' => $attendanceRate,
                'donation_rate' => $donationRate,
            ];
        });

        $totalCapacity = (int) $perDrive->sum('capacity');
        $totalBooked = (int) $perDrive->sum('booked');
        $totalAttended = (int) $perDrive->sum('attended');
        $totalNoShow = (int) $perDrive->sum('no_show');
        $totalDonated = (int) $perDrive->sum('donated');
        $totalDeferred = (int) $perDrive->sum('deferred');
        $totalWalkIns = (int) $perDrive->sum('walk_ins');

        // First-time donors: donors whose first-ever donated record falls in the period
        $firstTimeDonors = DonationRecord::query()
            ->where('outcome', DonationOutcome::Donated)
            ->whereBetween('recorded_at', [$startUtc, $endUtc])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('donation_records as prior')
                    ->whereColumn('prior.user_id', 'donation_records.user_id')
                    ->where('prior.outcome', DonationOutcome::Donated->value)
                    ->whereColumn('prior.recorded_at', '<', 'donation_records.recorded_at');
            })
            ->distinct('user_id')
            ->count('user_id');

        // New registrations: active donor accounts created in the period
        $newRegistrations = User::query()
            ->where('role', Role::Donor)
            ->where('is_active', true)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();

        // Overall Rates
        $attendanceDenominator = $totalAttended + $totalNoShow;
        $overallAttendanceRate = $attendanceDenominator > 0
            ? (float) ($totalAttended / $attendanceDenominator)
            : null;

        $overallTotalOutcomes = $totalDonated + $totalDeferred;
        $overallDonationRate = $overallTotalOutcomes > 0
            ? (float) ($totalDonated / $overallTotalOutcomes)
            : null;

        // Chart 1: Turnout by drive (limit to last 8 drives in period)
        $turnoutByDrive = $perDrive->take(-8)->values()->map(function (array $m) {
            return [
                'id' => $m['drive']->id,
                'title' => $m['drive']->title,
                'short_title' => mb_strimwidth($m['drive']->title, 0, 18, '..'),
                'expected' => $m['expected'],
                'attended' => $m['attended'],
                'donated' => $m['donated'],
            ];
        })->toArray();

        // Chart 2: Bookings over time by week (aggregated in PHP for cross-database speed)
        $appointmentsInPeriod = Appointment::query()
            ->join('slots', 'appointments.slot_id', '=', 'slots.id')
            ->whereIn('slots.drive_id', $driveIds)
            ->whereIn('appointments.status', [
                AppointmentStatus::Booked->value,
                AppointmentStatus::Attended->value,
                AppointmentStatus::NoShow->value,
            ])
            ->get(['appointments.created_at']);

        $weeklyMap = [];
        foreach ($appointmentsInPeriod as $appt) {
            $createdEat = Carbon::parse($appt->created_at)->setTimezone('Africa/Kampala');
            $weekKey = $createdEat->format('o-W');
            $weekLabel = 'Wk '.$createdEat->format('W');
            if (! isset($weeklyMap[$weekKey])) {
                $weeklyMap[$weekKey] = ['week_label' => $weekLabel, 'count' => 0];
            }
            $weeklyMap[$weekKey]['count']++;
        }
        ksort($weeklyMap);
        $bookingsByWeek = array_values($weeklyMap);

        return [
            'start_eat' => $boundaries['startEat'],
            'end_eat' => $boundaries['endEat'],
            'drives_held' => $drivesHeld,
            'capacity' => $totalCapacity,
            'booked' => $totalBooked,
            'attended' => $totalAttended,
            'no_show' => $totalNoShow,
            'donated' => $totalDonated,
            'deferred' => $totalDeferred,
            'walk_ins' => $totalWalkIns,
            'first_time_donors' => $firstTimeDonors,
            'new_registrations' => $newRegistrations,
            'attendance_rate' => $overallAttendanceRate,
            'donation_rate' => $overallDonationRate,
            'per_drive' => $perDrive,
            'turnout_by_drive' => $turnoutByDrive,
            'bookings_by_week' => $bookingsByWeek,
        ];
    }

    /**
     * Resolve the live drive today (EAT) or the next scheduled upcoming drive.
     *
     * @return array{
     *     status: 'live'|'next'|'none',
     *     drive: Drive|null,
     *     metrics: array<string, mixed>|null,
     *     days_to_go: int|null,
     *     announcement_sent: bool
     * }
     */
    public function getLiveOrNextDrive(): array
    {
        $todayEat = DateHelper::today();
        $todayEatDate = $todayEat->toDateString();

        // 1. Check for a live drive today (EAT)
        $liveDrive = Drive::query()
            ->whereIn('status', [DriveStatus::Published, DriveStatus::Closed])
            ->whereDate('starts_on', '<=', $todayEatDate)
            ->whereDate('ends_on', '>=', $todayEatDate)
            ->orderBy('starts_on')
            ->first();

        if ($liveDrive) {
            $announcementSent = $liveDrive->notificationLogs()
                ->where('type', 'new_drive_announcement')
                ->exists();

            return [
                'status' => 'live',
                'drive' => $liveDrive,
                'metrics' => $this->getDriveMetrics($liveDrive),
                'days_to_go' => 0,
                'announcement_sent' => $announcementSent,
            ];
        }

        // 2. Otherwise find the next published upcoming drive
        $nextDrive = Drive::query()
            ->where('status', DriveStatus::Published)
            ->whereDate('starts_on', '>', $todayEatDate)
            ->orderBy('starts_on')
            ->first();

        if ($nextDrive) {
            $daysToGo = $todayEat->diffInDays($nextDrive->starts_on, false);
            $announcementSent = $nextDrive->notificationLogs()
                ->where('type', 'new_drive_announcement')
                ->exists();

            return [
                'status' => 'next',
                'drive' => $nextDrive,
                'metrics' => $this->getDriveMetrics($nextDrive),
                'days_to_go' => (int) $daysToGo,
                'announcement_sent' => $announcementSent,
            ];
        }

        return [
            'status' => 'none',
            'drive' => null,
            'metrics' => null,
            'days_to_go' => null,
            'announcement_sent' => false,
        ];
    }

    /**
     * Resolve operational "Needs Attention" alert items.
     *
     * @return array<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     count: int,
     *     action_label: string,
     *     action_url: string,
     *     badge_variant: string
     * }>
     */
    public function getNeedsAttentionItems(): array
    {
        $items = [];
        $todayEat = DateHelper::today();
        $todayEatDate = $todayEat->toDateString();
        $threeDaysLaterEatDate = $todayEat->copy()->addDays(3)->toDateString();
        $lowFillThreshold = (float) config('donorlink.low_fill_threshold', 0.25);

        // 1. Ended drives that still have unrecorded appointments (close-out needed)
        $unclosedDrives = Drive::query()
            ->where('status', DriveStatus::Published)
            ->whereDate('ends_on', '<', $todayEatDate)
            ->whereHas('appointments', fn ($q) => $q->where('status', AppointmentStatus::Booked))
            ->withCount([
                'appointments as unrecorded_count' => fn ($q) => $q->where('status', AppointmentStatus::Booked),
            ])
            ->get();

        foreach ($unclosedDrives as $drive) {
            $items[] = [
                'type' => 'close_out_needed',
                'title' => "Close-out needed: {$drive->title}",
                'description' => "Drive ended on {$drive->ends_on->format('d M Y')} with {$drive->unrecorded_count} unrecorded booking(s).",
                'count' => (int) $drive->unrecorded_count,
                'action_label' => 'Open Roster',
                'action_url' => route('staff.drives.roster', $drive),
                'badge_variant' => 'error',
            ];
        }

        // 2. Appointments checked in without an outcome
        $unresolvedCheckIns = Appointment::query()
            ->where('status', AppointmentStatus::Booked)
            ->whereNotNull('checked_in_at')
            ->whereDoesntHave('donationRecord')
            ->with('slot.drive')
            ->get();

        if ($unresolvedCheckIns->isNotEmpty()) {
            $driveGrouped = $unresolvedCheckIns->groupBy('slot.drive_id');
            foreach ($driveGrouped as $driveId => $appts) {
                $drive = $appts->first()->slot?->drive;
                if ($drive) {
                    $items[] = [
                        'type' => 'checked_in_unresolved',
                        'title' => "{$appts->count()} checked-in donor(s) awaiting outcome",
                        'description' => "Donors arrived at {$drive->title} but outcome is not yet recorded.",
                        'count' => $appts->count(),
                        'action_label' => 'Resolve Outcomes',
                        'action_url' => route('staff.drives.roster', $drive),
                        'badge_variant' => 'warning',
                    ];
                }
            }
        }

        // 3. Published drives starting within 3 days with fill rate under threshold
        $upcomingDrivesSoon = Drive::query()
            ->where('status', DriveStatus::Published)
            ->whereDate('starts_on', '>=', $todayEatDate)
            ->whereDate('starts_on', '<=', $threeDaysLaterEatDate)
            ->with('slots')
            ->get();

        if ($upcomingDrivesSoon->isNotEmpty()) {
            $soonDriveIds = $upcomingDrivesSoon->pluck('id');
            $soonBookings = Appointment::query()
                ->join('slots', 'appointments.slot_id', '=', 'slots.id')
                ->whereIn('slots.drive_id', $soonDriveIds)
                ->whereIn('appointments.status', [
                    AppointmentStatus::Booked->value,
                    AppointmentStatus::Attended->value,
                    AppointmentStatus::NoShow->value,
                ])
                ->selectRaw('slots.drive_id, COUNT(*) as booked_count')
                ->groupBy('slots.drive_id')
                ->get()
                ->keyBy('drive_id');

            foreach ($upcomingDrivesSoon as $drive) {
                $capacity = (int) $drive->slots->sum('capacity');
                $booked = (int) ($soonBookings->get($drive->id)->booked_count ?? 0);
                $fillRate = $capacity > 0 ? (float) ($booked / $capacity) : 0.0;

                if ($capacity > 0 && $fillRate < $lowFillThreshold) {
                    $fillPercent = round($fillRate * 100, 1);
                    $thresholdPercent = round($lowFillThreshold * 100);
                    $items[] = [
                        'type' => 'low_fill_rate',
                        'title' => "Low fill rate ({$fillPercent}%): {$drive->title}",
                        'description' => "Starts on {$drive->starts_on->format('d M Y')}. Fill rate is below the {$thresholdPercent}% threshold ({$booked}/{$capacity} places booked).",
                        'count' => $booked,
                        'action_label' => 'View Drive',
                        'action_url' => route('staff.drives.show', $drive),
                        'badge_variant' => 'warning',
                    ];
                }
            }
        }

        // 4. Published upcoming drives with no announcement sent yet
        $unannouncedDrives = Drive::query()
            ->where('status', DriveStatus::Published)
            ->whereDate('starts_on', '>=', $todayEatDate)
            ->whereDoesntHave('notificationLogs', fn ($q) => $q->where('type', 'new_drive_announcement'))
            ->get();

        foreach ($unannouncedDrives as $drive) {
            $items[] = [
                'type' => 'announcement_needed',
                'title' => "Announcement not sent: {$drive->title}",
                'description' => "Published drive starting {$drive->starts_on->format('d M Y')} has not announced to opted-in donors.",
                'count' => 1,
                'action_label' => 'Send Announcement',
                'action_url' => route('staff.drives.show', $drive),
                'badge_variant' => 'primary',
            ];
        }

        return $items;
    }

    /**
     * Compute donor base aggregate statistics (strictly non-identifying).
     *
     * @return array{
     *     active_donors: int,
     *     verified_donors: int,
     *     donated_zero: int,
     *     donated_once: int,
     *     donated_multiple: int,
     *     announcement_opt_ins: int
     * }
     */
    public function getDonorBaseAggregates(): array
    {
        $baseQuery = User::query()
            ->where('role', Role::Donor)
            ->where('is_active', true);

        $activeDonors = (int) (clone $baseQuery)->count();
        $verifiedDonors = (int) (clone $baseQuery)->whereNotNull('email_verified_at')->count();
        $announcementOptIns = (int) (clone $baseQuery)->where('notify_new_drives', true)->count();

        // Donation count distribution per donor
        $donorCounts = DonationRecord::query()
            ->where('outcome', DonationOutcome::Donated)
            ->selectRaw('user_id, COUNT(*) as donation_count')
            ->groupBy('user_id')
            ->pluck('donation_count');

        $donatedOnce = 0;
        $donatedMultiple = 0;

        foreach ($donorCounts as $count) {
            if ($count === 1) {
                $donatedOnce++;
            } elseif ($count > 1) {
                $donatedMultiple++;
            }
        }

        $donatedZero = max(0, $activeDonors - ($donatedOnce + $donatedMultiple));

        return [
            'active_donors' => $activeDonors,
            'verified_donors' => $verifiedDonors,
            'donated_zero' => $donatedZero,
            'donated_once' => $donatedOnce,
            'donated_multiple' => $donatedMultiple,
            'announcement_opt_ins' => $announcementOptIns,
            'opted_in_donors' => $announcementOptIns,
        ];
    }
}
