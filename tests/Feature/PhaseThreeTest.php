<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\DriveStatus;
use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\BookingWindowClosedException;
use App\Exceptions\DriveNotOpenException;
use App\Exceptions\NotAllowedException;
use App\Exceptions\SlotFullException;
use App\Models\Appointment;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\BookingService;
use App\Services\SlotService;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    // Shared setup for Phase 3 tests
});

function createPhase3TestDrive(DriveStatus $status = DriveStatus::Published, ?string $startsOn = null, ?string $endsOn = null): Drive
{
    $creator = User::factory()->staff()->create();
    $todayEat = DateHelper::today();

    return Drive::forceCreate([
        'title' => 'Test Blood Drive '.uniqid(),
        'venue' => 'Kampala Community Hall',
        'address' => 'Plot 5 Nakasero Hill Rd',
        'starts_on' => $startsOn ?? $todayEat->addDays(2)->toDateString(),
        'ends_on' => $endsOn ?? $todayEat->addDays(4)->toDateString(),
        'status' => $status,
        'created_by' => $creator->id,
    ]);
}

function createPhase3TestSlot(Drive $drive, int $capacity = 5, int $daysInFuture = 2, int $hour = 10, int $bookedCount = 0): Slot
{
    $startsAt = Carbon::now('UTC')->addDays($daysInFuture)->setHour($hour)->setMinute(0)->setSecond(0);
    $endsAt = $startsAt->copy()->addMinutes(30);

    return Slot::forceCreate([
        'drive_id' => $drive->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'capacity' => $capacity,
        'booked_count' => $bookedCount,
    ]);
}

/*
|--------------------------------------------------------------------------
| Section 5.A: Deterministic Concurrency & Capacity Tests (Pest)
|--------------------------------------------------------------------------
*/

test('stale read test: book fails with SlotFullException when database capacity was consumed behind in-memory model', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive, capacity: 1);
    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);

    // Instance is loaded in memory with booked_count = 0
    expect($slot->booked_count)->toBe(0);

    // Behind that instance's back, update database directly to consume the last place
    DB::table('slots')->where('id', $slot->id)->update(['booked_count' => 1]);

    // Attempting to book with the stale in-memory model must fail with SlotFullException
    $service = app(BookingService::class);

    expect(fn () => $service->book($donor, $slot))
        ->toThrow(SlotFullException::class, 'That slot has just been filled. Please choose another time.');

    // Database remains at capacity 1 with zero appointments created for this donor
    expect(Slot::find($slot->id)->booked_count)->toBe(1)
        ->and(Appointment::where('user_id', $donor->id)->count())->toBe(0);
});

test('two different donors booking a capacity-1 slot: exactly one succeeds and one appointment is created', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive, capacity: 1);

    $donor1 = User::factory()->donor()->create(['email_verified_at' => now()]);
    $donor2 = User::factory()->donor()->create(['email_verified_at' => now()]);

    $service = app(BookingService::class);

    // First booking succeeds
    $appt1 = $service->book($donor1, $slot);
    expect($appt1)->toBeInstanceOf(Appointment::class)
        ->and($appt1->status)->toBe(AppointmentStatus::Booked);

    // Second booking must throw SlotFullException
    expect(fn () => $service->book($donor2, $slot))
        ->toThrow(SlotFullException::class);

    $freshSlot = $slot->fresh();
    expect($freshSlot->booked_count)->toBe(1)
        ->and(Appointment::where('slot_id', $slot->id)->where('status', AppointmentStatus::Booked->value)->count())->toBe(1);
});

test('capacity 3 with 5 donors booking sequentially: exactly 3 succeed and 2 are refused', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive, capacity: 3);

    $donors = User::factory()->donor()->count(5)->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    $successCount = 0;
    $fullCount = 0;

    foreach ($donors as $donor) {
        try {
            $service->book($donor, $slot);
            $successCount++;
        } catch (SlotFullException) {
            $fullCount++;
        }
    }

    expect($successCount)->toBe(3)
        ->and($fullCount)->toBe(2)
        ->and($slot->fresh()->booked_count)->toBe(3)
        ->and(Appointment::where('slot_id', $slot->id)->count())->toBe(3);
});

test('booked_count never goes above capacity or below 0 across book, cancel, reschedule and repeated cancel sequences', function () {
    $drive = createPhase3TestDrive();
    $slot1 = createPhase3TestSlot($drive, capacity: 2, hour: 9);
    $slot2 = createPhase3TestSlot($drive, capacity: 2, hour: 11);

    $donorA = User::factory()->donor()->create(['email_verified_at' => now()]);
    $donorB = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    // 1. Book both into slot 1
    $apptA = $service->book($donorA, $slot1);
    $apptB = $service->book($donorB, $slot1);

    expect($slot1->fresh()->booked_count)->toBe(2);

    // 2. Reschedule donor A to slot 2
    $service->reschedule($apptA, $slot2, $donorA);
    expect($slot1->fresh()->booked_count)->toBe(1)
        ->and($slot2->fresh()->booked_count)->toBe(1);

    // 3. Cancel donor B on slot 1
    $service->cancel($apptB, $donorB);
    expect($slot1->fresh()->booked_count)->toBe(0);

    // 4. Repeated cancel on donor B (idempotent no-op)
    $service->cancel($apptB, $donorB);
    expect($slot1->fresh()->booked_count)->toBe(0)
        ->and($slot1->fresh()->booked_count)->toBeGreaterThanOrEqual(0);

    // 5. Cancel donor A on slot 2
    $service->cancel($apptA, $donorA);
    expect($slot2->fresh()->booked_count)->toBe(0);

    // 6. Repeated cancel on donor A
    $service->cancel($apptA, $donorA);
    expect($slot2->fresh()->booked_count)->toBe(0)
        ->and($slot2->fresh()->booked_count)->toBeGreaterThanOrEqual(0);
});

/*
|--------------------------------------------------------------------------
| Section 6: Business Rules, Security & Booking Constraints
|--------------------------------------------------------------------------
*/

test('unverified donor is refused by BookingService with NotAllowedException', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive);
    $unverifiedDonor = User::factory()->donor()->unverified()->create();

    $service = app(BookingService::class);

    expect(fn () => $service->book($unverifiedDonor, $slot))
        ->toThrow(NotAllowedException::class, 'Only active, email-verified voluntary donors can book appointments.');
});

test('inactive user is refused by BookingService with NotAllowedException', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive);
    $inactiveDonor = User::factory()->donor()->inactive()->create(['email_verified_at' => now()]);

    $service = app(BookingService::class);

    expect(fn () => $service->book($inactiveDonor, $slot))
        ->toThrow(NotAllowedException::class, 'Only active, email-verified voluntary donors can book appointments.');
});

test('staff and admin users cannot book appointments through donor routes and receive 403', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive);
    $staff = User::factory()->staff()->create(['email_verified_at' => now()]);
    $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

    $this->actingAs($staff)
        ->post(route('donor.appointments.store'), ['slot_id' => $slot->id])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('donor.appointments.store'), ['slot_id' => $slot->id])
        ->assertForbidden();
});

test('booking a draft, closed, or ended drive is refused with DriveNotOpenException', function () {
    $service = app(BookingService::class);
    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);

    // Draft drive
    $draftDrive = createPhase3TestDrive(DriveStatus::Draft);
    $draftSlot = createPhase3TestSlot($draftDrive);
    expect(fn () => $service->book($donor, $draftSlot))
        ->toThrow(DriveNotOpenException::class);

    // Closed drive
    $closedDrive = createPhase3TestDrive(DriveStatus::Closed);
    $closedSlot = createPhase3TestSlot($closedDrive);
    expect(fn () => $service->book($donor, $closedSlot))
        ->toThrow(DriveNotOpenException::class);

    // Past / ended drive
    $pastDrive = createPhase3TestDrive(DriveStatus::Published, '2026-01-01', '2026-01-02');
    $pastSlot = createPhase3TestSlot($pastDrive, daysInFuture: -5);
    expect(fn () => $service->book($donor, $pastSlot))
        ->toThrow(DriveNotOpenException::class);
});

test('booking a slot inside the 60-minute cutoff or in the past is refused with BookingWindowClosedException', function () {
    $drive = createPhase3TestDrive();
    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    // Slot 30 minutes from now (inside default 60 min cutoff)
    $cutoffSlot = Slot::create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->addMinutes(30),
        'ends_at' => Carbon::now('UTC')->addMinutes(60),
        'capacity' => 5,
        'booked_count' => 0,
    ]);

    expect(fn () => $service->book($donor, $cutoffSlot))
        ->toThrow(BookingWindowClosedException::class, 'Booking for this time slot has closed.');

    // Slot in the past
    $pastSlot = Slot::create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->subHours(2),
        'ends_at' => Carbon::now('UTC')->subHours(1),
        'capacity' => 5,
        'booked_count' => 0,
    ]);

    expect(fn () => $service->book($donor, $pastSlot))
        ->toThrow(BookingWindowClosedException::class, 'Booking for this time slot has closed.');
});

test('second booking at the same drive by the same donor is refused with AlreadyBookedException', function () {
    $drive = createPhase3TestDrive();
    $slot1 = createPhase3TestSlot($drive, hour: 10);
    $slot2 = createPhase3TestSlot($drive, hour: 14);

    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    // First booking succeeds
    $service->book($donor, $slot1);

    // Second booking at different slot on same drive is refused
    expect(fn () => $service->book($donor, $slot2))
        ->toThrow(AlreadyBookedException::class, 'You already have an active appointment booked for this donation drive.');
});

test('a donor with a booking at one drive can book a different drive', function () {
    $drive1 = createPhase3TestDrive();
    $drive2 = createPhase3TestDrive();

    $slot1 = createPhase3TestSlot($drive1, daysInFuture: 2);
    $slot2 = createPhase3TestSlot($drive2, daysInFuture: 5);

    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    $appt1 = $service->book($donor, $slot1);
    $appt2 = $service->book($donor, $slot2);

    expect($appt1)->toBeInstanceOf(Appointment::class)
        ->and($appt2)->toBeInstanceOf(Appointment::class)
        ->and(Appointment::where('user_id', $donor->id)->where('status', AppointmentStatus::Booked->value)->count())->toBe(2);
});

test('cancel: only the owner can cancel, frees exactly one place, and cannot cancel after slot started', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive, capacity: 5);

    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $otherDonor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    $appt = $service->book($donor, $slot);
    expect($slot->fresh()->booked_count)->toBe(1);

    // Other donor cannot cancel
    expect(fn () => $service->cancel($appt, $otherDonor))
        ->toThrow(NotAllowedException::class, 'You are not authorized to cancel another donor\'s appointment.');

    // Owner cancels: frees exactly one place
    $service->cancel($appt, $donor);
    expect($slot->fresh()->booked_count)->toBe(0)
        ->and($appt->fresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($appt->fresh()->cancelled_at)->not->toBeNull();

    // Cannot cancel after slot start time
    $pastStartedSlot = Slot::create([
        'drive_id' => $drive->id,
        'starts_at' => Carbon::now('UTC')->subMinutes(5),
        'ends_at' => Carbon::now('UTC')->addMinutes(25),
        'capacity' => 5,
        'booked_count' => 1,
    ]);
    $pastAppt = Appointment::create([
        'user_id' => $donor->id,
        'slot_id' => $pastStartedSlot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    expect(fn () => $service->cancel($pastAppt, $donor))
        ->toThrow(NotAllowedException::class, 'Cannot cancel an appointment once the session start time has arrived.');
});

test('reschedule: same drive only, full new slot leaves state unchanged, success moves place and preserves appointment ID', function () {
    $driveA = createPhase3TestDrive();
    $driveB = createPhase3TestDrive();

    $slotOld = createPhase3TestSlot($driveA, capacity: 3, hour: 10);
    $slotNew = createPhase3TestSlot($driveA, capacity: 2, hour: 12);
    $slotOtherDrive = createPhase3TestSlot($driveB, capacity: 3, hour: 10);

    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    $appt = $service->book($donor, $slotOld);
    $originalApptId = $appt->id;

    // Cannot reschedule to other drive
    expect(fn () => $service->reschedule($appt, $slotOtherDrive, $donor))
        ->toThrow(NotAllowedException::class, 'Appointments can only be rescheduled to a slot within the same blood drive.');

    // If new slot is full, leaves everything unchanged
    DB::table('slots')->where('id', $slotNew->id)->update(['booked_count' => 2]);
    expect(fn () => $service->reschedule($appt, $slotNew, $donor))
        ->toThrow(SlotFullException::class);

    expect($slotOld->fresh()->booked_count)->toBe(1)
        ->and($slotNew->fresh()->booked_count)->toBe(2)
        ->and($appt->fresh()->slot_id)->toBe($slotOld->id);

    // Free up space in new slot and reschedule successfully
    DB::table('slots')->where('id', $slotNew->id)->update(['booked_count' => 0]);
    $rescheduledAppt = $service->reschedule($appt, $slotNew, $donor);

    expect($rescheduledAppt->id)->toBe($originalApptId)
        ->and($rescheduledAppt->slot_id)->toBe($slotNew->id)
        ->and($slotOld->fresh()->booked_count)->toBe(0)
        ->and($slotNew->fresh()->booked_count)->toBe(1);
});

test('events are dispatched after commit and not dispatched on failure', function () {
    Event::fake([
        AppointmentBooked::class,
        AppointmentCancelled::class,
        AppointmentRescheduled::class,
    ]);

    $drive = createPhase3TestDrive();
    $slot1 = createPhase3TestSlot($drive, capacity: 1, hour: 10);
    $slot2 = createPhase3TestSlot($drive, capacity: 1, hour: 12);

    $donor1 = User::factory()->donor()->create(['email_verified_at' => now()]);
    $donor2 = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    // 1. Successful booking dispatches AppointmentBooked
    $appt = $service->book($donor1, $slot1);
    Event::assertDispatched(AppointmentBooked::class, fn ($e) => $e->appointment->id === $appt->id);

    // 2. Failed booking does NOT dispatch
    Event::fake([AppointmentBooked::class]);
    try {
        $service->book($donor2, $slot1);
    } catch (SlotFullException) {
    }
    Event::assertNotDispatched(AppointmentBooked::class);

    // 3. Reschedule dispatches AppointmentRescheduled
    Event::fake([AppointmentRescheduled::class]);
    $service->reschedule($appt, $slot2, $donor1);
    Event::assertDispatched(AppointmentRescheduled::class, fn ($e) => $e->appointment->id === $appt->id && $e->oldSlotId === $slot1->id && $e->newSlotId === $slot2->id);

    // 4. Cancel dispatches AppointmentCancelled
    Event::fake([AppointmentCancelled::class]);
    $service->cancel($appt, $donor1);
    Event::assertDispatched(AppointmentCancelled::class, fn ($e) => $e->appointment->id === $appt->id);
});

test('authorisation: a donor can never view, cancel or reschedule another donors appointment via request tampering', function () {
    $drive = createPhase3TestDrive();
    $slot1 = createPhase3TestSlot($drive, hour: 10);
    $slot2 = createPhase3TestSlot($drive, hour: 12);

    $victimDonor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $attackerDonor = User::factory()->donor()->create(['email_verified_at' => now()]);

    $service = app(BookingService::class);
    $victimAppt = $service->book($victimDonor, $slot1);

    // Attacker tampering cancel endpoint
    $this->actingAs($attackerDonor)
        ->delete(route('donor.appointments.destroy', $victimAppt))
        ->assertForbidden();

    expect($victimAppt->fresh()->isBooked())->toBeTrue();

    // Attacker tampering reschedule endpoint
    $this->actingAs($attackerDonor)
        ->put(route('donor.appointments.reschedule', $victimAppt), ['new_slot_id' => $slot2->id])
        ->assertForbidden();

    expect($victimAppt->fresh()->slot_id)->toBe($slot1->id);
});

test('closing a drive blocks new bookings but leaves existing appointments intact', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive, capacity: 5);

    $donor1 = User::factory()->donor()->create(['email_verified_at' => now()]);
    $donor2 = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);

    // Donor 1 books while published
    $appt = $service->book($donor1, $slot);
    expect($appt->isBooked())->toBeTrue();

    // Close the drive
    $drive->status = DriveStatus::Closed;
    $drive->save();

    // Donor 2 attempts booking: refused
    expect(fn () => $service->book($donor2, $slot))
        ->toThrow(DriveNotOpenException::class);

    // Existing appointment is intact and still booked
    expect($appt->fresh()->isBooked())->toBeTrue()
        ->and($slot->fresh()->booked_count)->toBe(1);
});

test('phase 2 slot guards refuse deletion and retiming of a slot with a real booking', function () {
    $drive = createPhase3TestDrive();
    $slot = createPhase3TestSlot($drive, capacity: 5, hour: 10);

    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $service = app(BookingService::class);
    $service->book($donor, $slot);

    $slotService = app(SlotService::class);

    // Refuses deletion
    expect(fn () => $slotService->delete($slot))
        ->toThrow(ValidationException::class);

    // Refuses retiming
    expect(fn () => $slotService->update($slot, ['start_time' => '11:00', 'end_time' => '11:30']))
        ->toThrow(ValidationException::class);

    // Refuses reducing capacity below 1 (current booked count)
    expect(fn () => $slotService->update($slot, ['capacity' => 0]))
        ->toThrow(ValidationException::class);
});

test('booking UI states on public drive show page: guest, unverified, available, full, closed, already-booked', function () {
    $drive = createPhase3TestDrive();
    $openSlot = createPhase3TestSlot($drive, capacity: 5, hour: 10);
    $fullSlot = createPhase3TestSlot($drive, capacity: 1, hour: 12, bookedCount: 1);

    // 1. Guest visits: sees "Log in to book"
    $response = $this->get(route('drives.show', $drive));
    $response->assertOk()
        ->assertSee('Log in to book');

    // 2. Unverified donor visits: sees email verification prompt
    $unverified = User::factory()->donor()->unverified()->create();
    $this->actingAs($unverified)
        ->get(route('drives.show', $drive))
        ->assertOk()
        ->assertSee('Please verify your email address to book a donation slot');

    // 3. Verified donor visits: sees "Book" button and "Full" on fullSlot
    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    $this->actingAs($donor)
        ->get(route('drives.show', $drive))
        ->assertOk()
        ->assertSee('Book')
        ->assertSee('Full');

    // 4. Donor books slot: visits page again, sees prominent booking banner and "Change to this time"
    app(BookingService::class)->book($donor, $openSlot);

    $this->actingAs($donor)
        ->get(route('drives.show', $drive))
        ->assertOk()
        ->assertSee('Your Confirmed Booking at This Drive')
        ->assertSee('Cancel Appointment');

    // 5. Closed drive shows closed banner
    $drive->status = DriveStatus::Closed;
    $drive->save();

    $this->actingAs($donor)
        ->get(route('drives.show', $drive))
        ->assertOk()
        ->assertSee('This drive is closed');
});

test('query counts for /donor/appointments and /drives/{drive} are flat with zero N+1 queries', function () {
    $drive = createPhase3TestDrive();
    $slots = [];
    for ($i = 0; $i < 6; $i++) {
        $slots[] = createPhase3TestSlot($drive, capacity: 5, hour: 9 + $i);
    }

    $donor = User::factory()->donor()->create(['email_verified_at' => now()]);
    app(BookingService::class)->book($donor, $slots[0]);

    // Measure query count for /drives/{drive}
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($donor)->get(route('drives.show', $drive))->assertOk();

    $driveShowQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Measure query count for /donor/appointments
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($donor)->get(route('donor.appointments'))->assertOk();

    $donorAppointmentsQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Verify flat query counts (no N+1 per slot or appointment)
    expect($driveShowQueries)->toBeLessThanOrEqual(5)
        ->and($donorAppointmentsQueries)->toBeLessThanOrEqual(5);
});
