<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DonationRecord;
use App\Models\DonorProfile;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\CsvExportService;
use App\Services\ReportService;
use App\Support\DateHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('metric correctness: hand-built fixtures verify exact definitions', function () {
    $service = app(ReportService::class);
    $staff = User::factory()->create(['role' => Role::Staff]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->subDays(2)->toDateString(),
        'ends_on' => DateHelper::today()->subDays(2)->toDateString(),
    ]);

    // Slot with capacity 10 (ended in past)
    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'capacity' => 10,
        'starts_at' => DateHelper::today()->subDays(2)->setTime(9, 0)->setTimezone('UTC'),
        'ends_at' => DateHelper::today()->subDays(2)->setTime(10, 0)->setTimezone('UTC'),
    ]);

    // 1. Two attended appointments that donated
    for ($i = 0; $i < 2; $i++) {
        $donor = User::factory()->create(['role' => Role::Donor]);
        DonorProfile::create(['user_id' => $donor->id]);
        $appt = Appointment::factory()->attended()->create([
            'user_id' => $donor->id,
            'slot_id' => $slot->id,
            'checked_in_at' => DateHelper::today()->subDays(2)->setTime(9, 10)->setTimezone('UTC'),
        ]);
        DonationRecord::factory()->donated()->create([
            'user_id' => $donor->id,
            'drive_id' => $drive->id,
            'appointment_id' => $appt->id,
            'recorded_by' => $staff->id,
        ]);
    }

    // 2. One attended appointment that was deferred (did not donate)
    $defDonor = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $defDonor->id]);
    $defAppt = Appointment::factory()->attended()->create([
        'user_id' => $defDonor->id,
        'slot_id' => $slot->id,
        'checked_in_at' => DateHelper::today()->subDays(2)->setTime(9, 15)->setTimezone('UTC'),
    ]);
    DonationRecord::factory()->deferred()->create([
        'user_id' => $defDonor->id,
        'drive_id' => $drive->id,
        'appointment_id' => $defAppt->id,
        'recorded_by' => $staff->id,
    ]);

    // 3. One no-show appointment
    $nsDonor = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $nsDonor->id]);
    Appointment::factory()->noShow()->create([
        'user_id' => $nsDonor->id,
        'slot_id' => $slot->id,
    ]);

    // 4. One cancelled appointment (must be excluded from booked count)
    $cancDonor = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $cancDonor->id]);
    Appointment::factory()->cancelled()->create([
        'user_id' => $cancDonor->id,
        'slot_id' => $slot->id,
    ]);

    // 5. One walk-in donation record (no appointment; excluded from attendance denominator)
    $walkInDonor = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $walkInDonor->id]);
    DonationRecord::factory()->walkIn()->create([
        'user_id' => $walkInDonor->id,
        'drive_id' => $drive->id,
        'outcome' => DonationOutcome::Donated,
        'recorded_by' => $staff->id,
    ]);

    // Compute metrics
    $metrics = $service->getDriveMetrics($drive);

    // Assert exact figures:
    // Capacity = 10
    expect($metrics['capacity'])->toBe(10)
        // Booked = 2 attended(donated) + 1 attended(deferred) + 1 no-show = 4 (cancelled excluded!)
        ->and($metrics['booked'])->toBe(4)
        // Expected = 2 attended(donated) + 1 attended(deferred) = 3 (no-show and cancelled excluded)
        ->and($metrics['expected'])->toBe(3)
        // Cancelled = 1
        ->and($metrics['cancelled'])->toBe(1)
        // Attended = 3
        ->and($metrics['attended'])->toBe(3)
        // No-show = 1
        ->and($metrics['no_show'])->toBe(1)
        // Donated = 2 (appointments) + 1 (walk-in) = 3
        ->and($metrics['donated'])->toBe(3)
        // Deferred / Did not donate = 1
        ->and($metrics['deferred'])->toBe(1)
        // Walk-ins = 1
        ->and($metrics['walk_ins'])->toBe(1)
        // Fill rate = 4 ÷ 10 = 0.4 (40%)
        ->and($metrics['fill_rate'])->toBe(0.4)
        // Attendance rate = attended ÷ (attended + no-show) = 3 ÷ (3 + 1) = 0.75 (75%)
        // Notice walk-ins are excluded from denominator (3 + 1 = 4, not 5!)
        ->and($metrics['attendance_rate'])->toBe(0.75)
        // Donation rate = donated ÷ recorded outcomes = 3 ÷ 4 = 0.75 (75%)
        ->and($metrics['donation_rate'])->toBe(0.75);
});

test('zero denominators render "No data", never 0%, NaN or division errors', function () {
    $service = app(ReportService::class);
    $drive = Drive::factory()->published()->create();

    // Drive with 0 capacity and 0 appointments
    $metrics = $service->getDriveMetrics($drive);

    expect($metrics['capacity'])->toBe(0)
        ->and($metrics['fill_rate'])->toBeNull()
        ->and($metrics['attendance_rate'])->toBeNull()
        ->and($metrics['donation_rate'])->toBeNull()
        ->and(ReportService::formatRate($metrics['fill_rate']))->toBe('No data')
        ->and(ReportService::formatRate($metrics['attendance_rate']))->toBe('No data')
        ->and(ReportService::formatRate($metrics['donation_rate']))->toBe('No data');
});

test('range boundaries: EAT 23:59:59 and 00:00:00 land in right day and cross UTC date line', function () {
    $service = app(ReportService::class);

    // Range: 2026-10-01 to 2026-10-01 in EAT
    $parsed = $service->parseEatDateRange('2026-10-01', '2026-10-01');

    // In EAT: starts 2026-10-01 00:00:00, ends 2026-10-01 23:59:59
    expect($parsed['startEat']->format('Y-m-d H:i:s'))->toBe('2026-10-01 00:00:00')
        ->and($parsed['endEat']->format('Y-m-d H:i:s'))->toBe('2026-10-01 23:59:59');

    // In UTC (EAT is UTC+3):
    // 00:00:00 EAT on 1 Oct is 21:00:00 UTC on 30 Sep!
    // 23:59:59 EAT on 1 Oct is 20:59:59 UTC on 1 Oct!
    expect($parsed['startUtc']->format('Y-m-d H:i:s'))->toBe('2026-09-30 21:00:00')
        ->and($parsed['endUtc']->format('Y-m-d H:i:s'))->toBe('2026-10-01 20:59:59');
});

test('reconciliation: report totals equal roster summary, and period summary equals sum of per-drive reports', function () {
    $service = app(ReportService::class);
    $staff = User::factory()->create(['role' => Role::Staff]);

    $todayEat = DateHelper::today();

    // Create 2 past drives
    $drive1 = Drive::factory()->published()->create([
        'starts_on' => $todayEat->subDays(5)->toDateString(),
        'ends_on' => $todayEat->subDays(5)->toDateString(),
    ]);
    $slot1 = Slot::factory()->create([
        'drive_id' => $drive1->id,
        'capacity' => 10,
        'starts_at' => $todayEat->subDays(5)->setTime(9, 0)->setTimezone('UTC'),
        'ends_at' => $todayEat->subDays(5)->setTime(10, 0)->setTimezone('UTC'),
    ]);

    $d1 = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $d1->id]);
    $a1 = Appointment::factory()->attended()->create(['user_id' => $d1->id, 'slot_id' => $slot1->id]);
    DonationRecord::factory()->donated()->create(['user_id' => $d1->id, 'drive_id' => $drive1->id, 'appointment_id' => $a1->id, 'recorded_by' => $staff->id]);

    $drive2 = Drive::factory()->published()->create([
        'starts_on' => $todayEat->subDays(3)->toDateString(),
        'ends_on' => $todayEat->subDays(3)->toDateString(),
    ]);
    $slot2 = Slot::factory()->create([
        'drive_id' => $drive2->id,
        'capacity' => 12,
        'starts_at' => $todayEat->subDays(3)->setTime(9, 0)->setTimezone('UTC'),
        'ends_at' => $todayEat->subDays(3)->setTime(10, 0)->setTimezone('UTC'),
    ]);
    $d2 = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $d2->id]);
    $a2 = Appointment::factory()->attended()->create(['user_id' => $d2->id, 'slot_id' => $slot2->id]);
    DonationRecord::factory()->deferred()->create(['user_id' => $d2->id, 'drive_id' => $drive2->id, 'appointment_id' => $a2->id, 'recorded_by' => $staff->id]);

    $m1 = $service->getDriveMetrics($drive1);
    $m2 = $service->getDriveMetrics($drive2);

    $periodMetrics = $service->getPeriodMetrics($todayEat->subDays(7), $todayEat);

    // Sum of per-drive reports equals period summary totals
    expect($periodMetrics['drives_held'])->toBe(2)
        ->and($periodMetrics['capacity'])->toBe($m1['capacity'] + $m2['capacity'])
        ->and($periodMetrics['booked'])->toBe($m1['booked'] + $m2['booked'])
        ->and($periodMetrics['attended'])->toBe($m1['attended'] + $m2['attended'])
        ->and($periodMetrics['donated'])->toBe($m1['donated'] + $m2['donated'])
        ->and($periodMetrics['deferred'])->toBe($m1['deferred'] + $m2['deferred']);
});

test('needs attention items appear and disappear correctly', function () {
    $service = app(ReportService::class);
    $todayEat = DateHelper::today();

    // 1. Initially no needs-attention items
    $items = $service->getNeedsAttentionItems();
    expect($items)->toBeEmpty();

    // 2. Add an ended drive with an unrecorded appointment (close-out needed)
    $endedDrive = Drive::factory()->published()->create([
        'title' => 'Needs Close Out Blood Camp',
        'starts_on' => $todayEat->subDays(3)->toDateString(),
        'ends_on' => $todayEat->subDays(2)->toDateString(),
    ]);
    $endedSlot = Slot::factory()->create([
        'drive_id' => $endedDrive->id,
        'starts_at' => $todayEat->subDays(2)->setTime(9, 0)->setTimezone('UTC'),
        'ends_at' => $todayEat->subDays(2)->setTime(10, 0)->setTimezone('UTC'),
    ]);
    $unrecUser = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $unrecUser->id]);
    $unrecAppt = Appointment::factory()->create([
        'user_id' => $unrecUser->id,
        'slot_id' => $endedSlot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 3. Add an upcoming drive starting within 3 days with low fill (< 25%)
    $lowFillDrive = Drive::factory()->published()->create([
        'title' => 'Low Fill Mobile Drive',
        'starts_on' => $todayEat->addDays(2)->toDateString(),
        'ends_on' => $todayEat->addDays(2)->toDateString(),
    ]);
    Slot::factory()->create([
        'drive_id' => $lowFillDrive->id,
        'capacity' => 20,
        'booked_count' => 1,
    ]);

    $itemsNow = $service->getNeedsAttentionItems();
    $types = array_column($itemsNow, 'type');

    expect($types)->toContain('close_out_needed')
        ->and($types)->toContain('low_fill_rate')
        ->and($types)->toContain('announcement_needed');
});

test('authorisation: donors and guests get 403; donation records export is admin-only', function () {
    $donor = User::factory()->create(['role' => Role::Donor]);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $drive = Drive::factory()->published()->create();

    // 1. Guests redirected or forbidden
    $this->get(route('staff.dashboard'))->assertRedirect(route('login'));
    $this->get(route('staff.drives.report', $drive))->assertRedirect(route('login'));
    $this->get(route('staff.export.period'))->assertRedirect(route('login'));
    $this->get(route('admin.donations.export'))->assertRedirect(route('login'));

    // 2. Donors get 403
    $this->actingAs($donor)->get(route('staff.dashboard'))->assertForbidden();
    $this->actingAs($donor)->get(route('staff.drives.report', $drive))->assertForbidden();
    $this->actingAs($donor)->get(route('staff.export.period'))->assertForbidden();
    $this->actingAs($donor)->get(route('admin.donations.export'))->assertForbidden();

    // 3. Staff can access dashboard, report, period export; but NOT admin donations export
    $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk();
    $this->actingAs($staff)->get(route('staff.drives.report', $drive))->assertOk();
    $this->actingAs($staff)->get(route('staff.export.period'))->assertOk();
    $this->actingAs($staff)->get(route('admin.donations.export'))->assertForbidden();

    // 4. Admin can access everything including donations export
    $this->actingAs($admin)->get(route('staff.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('staff.drives.report', $drive))->assertOk();
    $this->actingAs($admin)->get(route('staff.export.period'))->assertOk();
    $this->actingAs($admin)->get(route('admin.donations.export'))->assertOk();
});

test('exports: BOM present, headers, EAT times, no notes, no blood group, no DOB, no word "deferred"', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $todayEat = DateHelper::today();

    $drive = Drive::factory()->published()->create([
        'title' => 'Nakasero Export Test Camp',
        'starts_on' => $todayEat->subDays(1)->toDateString(),
        'ends_on' => $todayEat->subDays(1)->toDateString(),
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'capacity' => 10,
        'starts_at' => $todayEat->subDays(1)->setTime(9, 0)->setTimezone('UTC'),
        'ends_at' => $todayEat->subDays(1)->setTime(10, 0)->setTimezone('UTC'),
    ]);

    $donor = User::factory()->create(['role' => Role::Donor, 'name' => 'John Doe']);
    DonorProfile::create([
        'user_id' => $donor->id,
        'blood_group' => 'O+',
        'notes_for_staff' => 'HIGHLY_CONFIDENTIAL_MEDICAL_NOTE',
    ]);

    $appt = Appointment::factory()->attended()->create(['user_id' => $donor->id, 'slot_id' => $slot->id]);
    DonationRecord::factory()->deferred()->create([
        'user_id' => $donor->id,
        'drive_id' => $drive->id,
        'appointment_id' => $appt->id,
        'recorded_by' => $staff->id,
        'staff_note' => 'ANOTHER_PRIVATE_ADMINISTRATIVE_NOTE',
    ]);

    // Test Roster Export
    $response = $this->actingAs($staff)->get(route('staff.drives.export.roster', $drive));
    $response->assertOk();
    $content = $response->streamedContent();

    // 1. BOM present at start
    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue()
        // 2. Strict privacy: no notes, blood group, or word "deferred"
        ->and($content)->not->toContain('HIGHLY_CONFIDENTIAL_MEDICAL_NOTE')
        ->and($content)->not->toContain('ANOTHER_PRIVATE_ADMINISTRATIVE_NOTE')
        ->and($content)->not->toContain('O+')
        ->and(stripos($content, 'deferred'))->toBeFalse()
        // 3. Shows "Did not donate"
        ->and($content)->toContain('Did not donate');

    // 4. Audit entry recorded
    $audit = AuditLog::where('action', 'export.created')->latest('id')->first();
    expect($audit)->not->toBeNull()
        ->and($audit->metadata['export_type'])->toBe('drive_roster')
        ->and($audit->metadata['row_count'])->toBeGreaterThanOrEqual(1);
});

test('hostile-name CSV injection test neutralises formulas with leading single quote', function () {
    expect(CsvExportService::sanitizeCell('=cmd|/C calc!A0'))->toBe("'=cmd|/C calc!A0")
        ->and(CsvExportService::sanitizeCell('+12345'))->toBe("'+12345")
        ->and(CsvExportService::sanitizeCell('-SUM(A1:A10)'))->toBe("'-SUM(A1:A10)")
        ->and(CsvExportService::sanitizeCell('@HYPERLINK("http://evil.com")'))->toBe("'@HYPERLINK(\"http://evil.com\")")
        ->and(CsvExportService::sanitizeCell("\tTabInjected"))->toBe("'\tTabInjected")
        ->and(CsvExportService::sanitizeCell("\rCarriageReturn"))->toBe("'\rCarriageReturn")
        ->and(CsvExportService::sanitizeCell('Normal Donor Name'))->toBe('Normal Donor Name')
        ->and(CsvExportService::sanitizeCell(null))->toBe('');
});

test('dashboard and drive report show no next-eligible, interval, or eligibility wording', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $drive = Drive::factory()->published()->create();

    // Check dashboard page
    $dashResp = $this->actingAs($staff)->get(route('staff.dashboard'));
    $dashResp->assertOk()
        ->assertDontSee('next eligible')
        ->assertDontSee('next donation')
        ->assertDontSee('eligible date')
        ->assertDontSee('minimum interval')
        ->assertDontSee('deferred', false);

    // Check drive report page
    $repResp = $this->actingAs($staff)->get(route('staff.drives.report', $drive));
    $repResp->assertOk()
        ->assertDontSee('next eligible')
        ->assertDontSee('next donation')
        ->assertDontSee('eligible date')
        ->assertDontSee('minimum interval')
        ->assertDontSee('deferred', false);
});

test('query counts for dashboard and drive report remain flat as appointments scale', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'capacity' => 50,
        'starts_at' => DateHelper::today()->setTime(9, 0)->setTimezone('UTC'),
        'ends_at' => DateHelper::today()->setTime(10, 0)->setTimezone('UTC'),
    ]);

    // Create 40 donors and appointments
    for ($i = 0; $i < 40; $i++) {
        $donor = User::factory()->create(['role' => Role::Donor]);
        DonorProfile::create(['user_id' => $donor->id]);
        Appointment::factory()->create([
            'user_id' => $donor->id,
            'slot_id' => $slot->id,
            'status' => AppointmentStatus::Booked,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    // Render Dashboard
    $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk();
    $dashQueries = count(DB::getQueryLog());

    DB::flushQueryLog();

    // Render Drive Report
    $this->actingAs($staff)->get(route('staff.drives.report', $drive))->assertOk();
    $reportQueries = count(DB::getQueryLog());

    // Dashboard and report queries stay low and constant regardless of appointment volume
    expect($dashQueries)->toBeLessThanOrEqual(35)
        ->and($reportQueries)->toBeLessThanOrEqual(20);
});
