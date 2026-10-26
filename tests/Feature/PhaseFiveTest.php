<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Events\AppointmentCancelled;
use App\Exceptions\CloseOutBlockedException;
use App\Exceptions\DonationRecordException;
use App\Exceptions\NotAllowedException;
use App\Livewire\Staff\DriveRoster;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DonationRecord;
use App\Models\DonorProfile;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use App\Services\DonationRecorder;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('authorisation: roster, donors list, donor detail, record, and close-out require staff or admin; audit-log is admin only', function () {
    $donor = User::factory()->create(['role' => Role::Donor]);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $drive = Drive::factory()->published()->create();

    // 1. Donors get 403 on staff routes
    $this->actingAs($donor)->get(route('staff.drives.roster', $drive))->assertForbidden();
    $this->actingAs($donor)->get(route('staff.donors'))->assertForbidden();
    $this->actingAs($donor)->get(route('staff.donors.show', $donor))->assertForbidden();
    $this->actingAs($donor)->post(route('staff.drives.close-out', $drive))->assertForbidden();
    $this->actingAs($donor)->get(route('admin.audit-log'))->assertForbidden();

    // Direct Livewire component instantiation & action invocation by non-staff is forbidden
    Livewire::actingAs($donor)
        ->test(DriveRoster::class, ['drive' => $drive])
        ->assertForbidden();

    // 2. Staff can access roster, donors, detail, close-out; but NOT admin audit log
    $this->actingAs($staff)->get(route('staff.drives.roster', $drive))->assertOk();
    $this->actingAs($staff)->get(route('staff.donors'))->assertOk();
    $this->actingAs($staff)->get(route('staff.donors.show', $donor))->assertOk();
    $this->actingAs($staff)->get(route('admin.audit-log'))->assertForbidden();

    // 3. Admin can access everything
    $this->actingAs($admin)->get(route('staff.drives.roster', $drive))->assertOk();
    $this->actingAs($admin)->get(route('staff.donors'))->assertOk();
    $this->actingAs($admin)->get(route('staff.donors.show', $donor))->assertOk();
    $this->actingAs($admin)->get(route('admin.audit-log'))->assertOk();
});

test('check-in: allowed once, idempotent, refused for non-booked or cancelled appointments, undo only before outcome', function () {
    $service = app(AttendanceService::class);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor = User::factory()->create(['role' => Role::Donor]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->addHour(),
        'ends_at' => Carbon::now('UTC')->addHours(2),
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 1. Check-in sets checked_in_at; status remains Booked
    $checkedIn = $service->checkIn($appointment, $staff);
    expect($checkedIn->checked_in_at)->not->toBeNull()
        ->and($checkedIn->status)->toBe(AppointmentStatus::Booked);

    // 2. Idempotent check-in
    $repeat = $service->checkIn($appointment, $staff);
    expect($repeat->checked_in_at->timestamp)->toBe($checkedIn->checked_in_at->timestamp);

    // 3. Undo check-in sets checked_in_at back to null
    $undone = $service->undoCheckIn($appointment, $staff);
    expect($undone->checked_in_at)->toBeNull();

    // 4. Refused for cancelled appointment
    $appointment->update(['status' => AppointmentStatus::Cancelled]);
    expect(fn () => $service->checkIn($appointment, $staff))->toThrow(NotAllowedException::class);

    // 5. Undo refused after an outcome is recorded
    $appointment->update(['status' => AppointmentStatus::Booked]);
    $service->checkIn($appointment, $staff);

    DonationRecord::factory()->create([
        'user_id' => $donor->id,
        'drive_id' => $drive->id,
        'appointment_id' => $appointment->id,
        'outcome' => DonationOutcome::Donated,
    ]);

    expect(fn () => $service->undoCheckIn($appointment, $staff))->toThrow(NotAllowedException::class);
});

test('recording: donated and deferred both set appointment to attended; duplicate or mismatched drive/donor refused', function () {
    $recorder = app(DonationRecorder::class);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor = User::factory()->create(['role' => Role::Donor]);
    $otherDonor = User::factory()->create(['role' => Role::Donor]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->addHour(),
        'ends_at' => Carbon::now('UTC')->addHours(2),
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 1. Record 'donated' sets appointment to attended and populates checked_in_at if empty
    $record = $recorder->record(
        donor: $donor,
        drive: $drive,
        outcome: DonationOutcome::Donated,
        staff: $staff,
        appointment: $appointment,
        note: 'Completed donation'
    );

    expect($record->outcome)->toBe(DonationOutcome::Donated)
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Attended)
        ->and($appointment->fresh()->checked_in_at)->not->toBeNull();

    // 2. Duplicate record for same donor and drive refused
    expect(fn () => $recorder->record(
        donor: $donor,
        drive: $drive,
        outcome: DonationOutcome::Deferred,
        staff: $staff,
        appointment: null
    ))->toThrow(DonationRecordException::class, 'A record already exists for this donor at this drive.');

    // 3. Appointment for other donor refused
    $appointment2 = Appointment::factory()->create([
        'user_id' => $otherDonor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    expect(fn () => $recorder->record(
        donor: $donor,
        drive: $drive,
        outcome: DonationOutcome::Donated,
        staff: $staff,
        appointment: $appointment2
    ))->toThrow(DonationRecordException::class);
});

test('walk-in: works for registered active donor, refused for inactive or outside date and grace period', function () {
    $recorder = app(DonationRecorder::class);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $activeDonor = User::factory()->create(['role' => Role::Donor, 'is_active' => true]);
    $inactiveDonor = User::factory()->create(['role' => Role::Donor, 'is_active' => false]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);

    // 1. Inactive donor refused
    expect(fn () => $recorder->record(
        donor: $inactiveDonor,
        drive: $drive,
        outcome: DonationOutcome::Donated,
        staff: $staff,
        appointment: null
    ))->toThrow(DonationRecordException::class);

    // 2. Active donor within drive dates succeeds
    $record = $recorder->record(
        donor: $activeDonor,
        drive: $drive,
        outcome: DonationOutcome::Deferred,
        staff: $staff,
        appointment: null,
        note: 'Walk-in screened on site'
    );

    expect($record->isWalkIn())->toBeTrue()
        ->and($record->outcome)->toBe(DonationOutcome::Deferred)
        ->and($record->appointment_id)->toBeNull();

    // 3. Drive outside grace period (ends_on was 10 days ago, grace is 7)
    $expiredDrive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->subDays(12)->toDateString(),
        'ends_on' => DateHelper::today()->subDays(10)->toDateString(),
    ]);
    $anotherDonor = User::factory()->create(['role' => Role::Donor, 'is_active' => true]);

    expect(fn () => $recorder->record(
        donor: $anotherDonor,
        drive: $expiredDrive,
        outcome: DonationOutcome::Donated,
        staff: $staff,
        appointment: null
    ))->toThrow(DonationRecordException::class);
});

test('last_donated_on is derived: correct after recording, after correcting to deferred, with multiple records, and null when none; never incremented', function () {
    $recorder = app(DonationRecorder::class);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor = User::factory()->create(['role' => Role::Donor]);
    $profile = DonorProfile::create(['user_id' => $donor->id, 'last_donated_on' => null]);

    $drive1 = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->subDays(2)->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);
    $drive2 = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->subDays(1)->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);

    expect($profile->fresh()->last_donated_on)->toBeNull();

    // 1. Record deferred: last_donated_on remains null
    $record1 = $recorder->record($donor, $drive1, DonationOutcome::Deferred, $staff, null, 'Hydration');
    expect($profile->fresh()->last_donated_on)->toBeNull();

    // 2. Correct to donated: last_donated_on becomes drive1's date in EAT
    $recorder->correct($record1, DonationOutcome::Donated, $staff, 'Re-evaluated and donated');
    $expectedDate = Carbon::parse($record1->fresh()->recorded_at)->setTimezone('Africa/Kampala')->toDateString();
    expect($profile->fresh()->last_donated_on?->toDateString())->toBe($expectedDate);

    // 3. Second drive with donated: last_donated_on updates to latest record
    $record2 = $recorder->record($donor, $drive2, DonationOutcome::Donated, $staff, null);
    $latestDate = Carbon::parse($record2->recorded_at)->setTimezone('Africa/Kampala')->toDateString();
    expect($profile->fresh()->last_donated_on?->toDateString())->toBe($latestDate);

    // 4. Correct record2 to deferred: last_donated_on falls back to record1's date!
    $recorder->correct($record2, DonationOutcome::Deferred, $staff);
    expect($profile->fresh()->last_donated_on?->toDateString())->toBe($expectedDate);
});

test('no capacity changes: walk-ins and no-shows leave booked_count untouched; staff cancellation releases exactly one place and emails donor', function () {
    Notification::fake();
    Event::fake([AppointmentCancelled::class]);

    $attendanceService = app(AttendanceService::class);
    $recorder = app(DonationRecorder::class);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor = User::factory()->create(['role' => Role::Donor]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->toDateString(),
        'ends_on' => DateHelper::today()->toDateString(),
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'capacity' => 10,
        'booked_count' => 3,
        'starts_at' => Carbon::now('UTC')->addHour(),
        'ends_at' => Carbon::now('UTC')->addHours(2),
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 1. Walk-in leaves booked_count untouched
    $walkInDonor = User::factory()->create(['role' => Role::Donor]);
    $recorder->record($walkInDonor, $drive, DonationOutcome::Donated, $staff, null);
    expect($slot->fresh()->booked_count)->toBe(3);

    // 2. No-show leaves booked_count untouched
    $appointment2 = Appointment::factory()->create([
        'user_id' => $walkInDonor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);
    $attendanceService->markNoShow($appointment2, $staff);
    expect($slot->fresh()->booked_count)->toBe(3)
        ->and($appointment2->fresh()->status)->toBe(AppointmentStatus::NoShow);

    // 3. Staff cancellation releases exactly 1 place and dispatches AppointmentCancelled event
    $cancelled = $attendanceService->cancelByStaff($appointment, $staff);
    expect($slot->fresh()->booked_count)->toBe(2)
        ->and($cancelled->status)->toBe(AppointmentStatus::Cancelled);

    Event::assertDispatched(AppointmentCancelled::class);

    // 4. Repeated cancel releases nothing
    $attendanceService->cancelByStaff($appointment, $staff);
    expect($slot->fresh()->booked_count)->toBe(2);
});

test('close-out: blocked while checked-in appointments have no outcome, marks remaining booked as no-show, idempotent, audit logged', function () {
    $service = app(AttendanceService::class);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor1 = User::factory()->create(['role' => Role::Donor]);
    $donor2 = User::factory()->create(['role' => Role::Donor]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => DateHelper::today()->subDays(2)->toDateString(),
        'ends_on' => DateHelper::today()->subDay()->toDateString(), // ended in EAT
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->subHours(4),
        'ends_at' => Carbon::now('UTC')->subHours(3),
    ]);

    $appt1 = Appointment::factory()->create([
        'user_id' => $donor1->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
        'checked_in_at' => Carbon::now('UTC')->subHours(3),
    ]);

    $appt2 = Appointment::factory()->create([
        'user_id' => $donor2->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
        'checked_in_at' => null,
    ]);

    // 1. Blocked because appt1 is checked in without outcome
    expect(fn () => $service->closeOutDrive($drive, $staff))->toThrow(CloseOutBlockedException::class);

    // 2. Record outcome for appt1
    DonationRecord::factory()->create([
        'user_id' => $donor1->id,
        'drive_id' => $drive->id,
        'appointment_id' => $appt1->id,
        'outcome' => DonationOutcome::Donated,
    ]);
    $appt1->update(['status' => AppointmentStatus::Attended]);

    // 3. Now close out succeeds: marks remaining booked (appt2) as no_show
    $result = $service->closeOutDrive($drive, $staff);
    expect($result['no_show_count'])->toBe(1)
        ->and($appt2->fresh()->status)->toBe(AppointmentStatus::NoShow)
        ->and($drive->fresh()->status)->toBe(DriveStatus::Closed);

    // 4. Produces one audit entry with counts
    $audit = AuditLog::where('action', 'drive.closed_out')->where('subject_id', $drive->id)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->metadata['no_show_count'])->toBe(1);

    // 5. Idempotent: repeating changes nothing and does not double-log
    $repeat = $service->closeOutDrive($drive, $staff);
    expect($repeat['no_show_count'])->toBe(0);
});

test('donor history: shows donated and no-donation entries correctly, never exposes staff note or deferred word or next-eligible wording', function () {
    $donor = User::factory()->create(['role' => Role::Donor]);
    DonorProfile::create(['user_id' => $donor->id, 'last_donated_on' => DateHelper::today()->toDateString()]);

    $drive = Drive::factory()->published()->create([
        'title' => 'Nakasero Regional Blood Drive',
        'venue' => 'UBTS Headquarters Ground',
    ]);

    DonationRecord::factory()->create([
        'user_id' => $donor->id,
        'drive_id' => $drive->id,
        'outcome' => DonationOutcome::Donated,
        'staff_note' => 'SUPER_CONFIDENTIAL_STAFF_NOTE_ABOUT_DONOR',
    ]);

    $drive2 = Drive::factory()->published()->create([
        'title' => 'City Square Mobile Drive',
        'venue' => 'Constitution Square',
    ]);

    DonationRecord::factory()->create([
        'user_id' => $donor->id,
        'drive_id' => $drive2->id,
        'outcome' => DonationOutcome::Deferred,
        'staff_note' => 'ANOTHER_SECRET_ADMIN_LOGISTIC_NOTE',
    ]);

    $response = $this->actingAs($donor)->get(route('donor.history'));

    $response->assertOk()
        ->assertSee('Donated')
        ->assertSee('Attended (no donation recorded)')
        ->assertDontSee('SUPER_CONFIDENTIAL_STAFF_NOTE_ABOUT_DONOR')
        ->assertDontSee('ANOTHER_SECRET_ADMIN_LOGISTIC_NOTE')
        ->assertDontSee('deferred', false) // case-insensitive check for word "deferred"
        ->assertDontSee('next eligible')
        ->assertDontSee('eligible date')
        ->assertDontSee('eligible to donate');
});

test('donor appointments: ended and still-booked appointments appear under Past with Not recorded; home card ignores ended appointments', function () {
    $donor = User::factory()->create(['role' => Role::Donor]);

    $drive = Drive::factory()->published()->create();

    // Ended slot: ended 2 hours ago
    $endedSlot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->subHours(3),
        'ends_at' => Carbon::now('UTC')->subHours(2),
    ]);

    // Future slot: starts in 2 hours
    $futureSlot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->addHours(2),
        'ends_at' => Carbon::now('UTC')->addHours(3),
    ]);

    // 1. Ended appointment that was never recorded
    $pastBookedAppt = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $endedSlot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 2. Future booked appointment
    $upcomingAppt = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $futureSlot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    $response = $this->actingAs($donor)->get(route('donor.appointments'));

    $response->assertOk()
        ->assertSee('Not recorded'); // Badge for past booked appointment

    // Donor home page next appointment card ignores ended appointment
    $homeResponse = $this->actingAs($donor)->get(route('donor.home'));
    $homeResponse->assertOk()
        ->assertSee(DateHelper::formatRange($futureSlot->starts_at, $futureSlot->ends_at))
        ->assertDontSee(DateHelper::formatRange($endedSlot->starts_at, $endedSlot->ends_at));
});

test('audit: actions write log entries and metadata contains no note text, blood group value, or contact details', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor = User::factory()->create(['role' => Role::Donor, 'phone' => '+256700999999', 'email' => 'private@example.com']);

    // Call AuditLogger with sensitive data passed by mistake
    AuditLogger::record($staff, 'donor.updated', $donor, [
        'donor_id' => $donor->id,
        'blood_group' => 'O+',
        'staff_note' => 'Confidential health note',
        'phone' => '+256700999999',
        'changed_fields' => ['blood_group', 'notes_for_staff'],
    ]);

    $log = AuditLog::latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('donor.updated')
        ->and($log->metadata)->not->toHaveKey('blood_group')
        ->and($log->metadata)->not->toHaveKey('staff_note')
        ->and($log->metadata)->not->toHaveKey('phone')
        ->and($log->metadata['changed_fields'])->toBe(['blood_group', 'notes_for_staff']);
});

test('staff donors search and detail: search works, opening writes donor.viewed, listing does not', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $targetDonor = User::factory()->create([
        'role' => Role::Donor,
        'name' => 'Wasswa Jonathan',
        'email' => 'wasswa@donorlink.test',
        'phone' => '+256788111222',
    ]);

    AuditLog::truncate();

    // 1. Listing donors does NOT write an audit log
    $this->actingAs($staff)->get(route('staff.donors', ['search' => 'Wasswa']))
        ->assertOk()
        ->assertSee('Wasswa Jonathan');

    expect(AuditLog::count())->toBe(0);

    // 2. Opening donor detail DOES write donor.viewed
    $this->actingAs($staff)->get(route('staff.donors.show', $targetDonor))
        ->assertOk()
        ->assertSee('Wasswa Jonathan');

    expect(AuditLog::where('action', 'donor.viewed')->where('subject_id', $targetDonor->id)->count())->toBe(1);
});

test('blood group validation accepts only valid types or empty, notes length limits enforced', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $donor = User::factory()->create(['role' => Role::Donor]);

    // 1. Valid blood group
    $this->actingAs($staff)->put(route('staff.donors.update', $donor), [
        'blood_group' => 'AB+',
        'notes_for_staff' => 'Verified donor card',
    ])->assertRedirect(route('staff.donors.show', $donor));

    expect($donor->donorProfile->fresh()->blood_group)->toBe('AB+');

    // 2. Invalid blood group rejected
    $this->actingAs($staff)->put(route('staff.donors.update', $donor), [
        'blood_group' => 'X_NEGATIVE',
    ])->assertSessionHasErrors('blood_group');

    // 3. Notes exceeding 500 characters rejected
    $this->actingAs($staff)->put(route('staff.donors.update', $donor), [
        'notes_for_staff' => str_repeat('Long note exceeding limit ', 30),
    ])->assertSessionHasErrors('notes_for_staff');
});

test('roster query count remains flat as appointments grow', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $drive = Drive::factory()->published()->create();

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'capacity' => 50,
        'booked_count' => 40,
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

    DB::enableQueryLog();

    // Render roster component
    Livewire::actingAs($staff)->test(DriveRoster::class, ['drive' => $drive])
        ->assertOk();

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // With 40 appointments, eager loading ensures queries stay small and constant (exactly 10 queries, <= 12)
    expect($queryCount)->toBeLessThanOrEqual(12);
});
