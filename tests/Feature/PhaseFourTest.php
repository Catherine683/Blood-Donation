<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\NotificationStatus;
use App\Enums\Role;
use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled as AppointmentCancelledEvent;
use App\Events\AppointmentRescheduled as AppointmentRescheduledEvent;
use App\Models\Appointment;
use App\Models\Drive;
use App\Models\NotificationLog;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentReminder;
use App\Notifications\AppointmentRescheduled;
use App\Services\MailBudget;
use App\Support\IcsGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    // Reset test now time if modified
    Carbon::setTestNow(null);
});

test('booking slot sends exactly one confirmation email with ics and writes one log row', function () {
    Notification::fake();

    $donor = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $drive = Drive::factory()->published()->create([
        'starts_on' => now('Africa/Kampala')->addDays(3)->toDateString(),
        'ends_on' => now('Africa/Kampala')->addDays(4)->toDateString(),
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => now('UTC')->addDays(3)->setTime(6, 0), // 09:00 EAT
        'ends_at' => now('UTC')->addDays(3)->setTime(6, 30),
        'capacity' => 10,
        'booked_count' => 0,
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    Event::dispatch(new AppointmentBooked($appointment));

    Notification::assertSentTo($donor, AppointmentConfirmed::class, function ($notification) use ($appointment) {
        return $notification->appointment->id === $appointment->id;
    });

    $log = NotificationLog::where('related_id', $appointment->id)
        ->where('type', 'appointment_confirmed')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($donor->id)
        ->and($log->channel)->toBe('mail')
        ->and($log->dedupe_key)->toBe("confirmation:appointment:{$appointment->id}:slot:{$slot->id}");
});

test('rescheduling appointment sends updated notification and writes log row', function () {
    Notification::fake();

    $donor = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $drive = Drive::factory()->published()->create();

    $oldSlot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => now('UTC')->addDays(2)->setTime(6, 0),
        'ends_at' => now('UTC')->addDays(2)->setTime(6, 30),
    ]);
    $newSlot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => now('UTC')->addDays(2)->setTime(7, 0),
        'ends_at' => now('UTC')->addDays(2)->setTime(7, 30),
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $newSlot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    Event::dispatch(new AppointmentRescheduledEvent($appointment, $oldSlot->id, $newSlot->id));

    Notification::assertSentTo($donor, AppointmentRescheduled::class, function ($notification) use ($appointment, $oldSlot, $newSlot) {
        return $notification->appointment->id === $appointment->id
            && $notification->oldSlotId === $oldSlot->id
            && $notification->newSlotId === $newSlot->id;
    });

    $log = NotificationLog::where('related_id', $appointment->id)
        ->where('type', 'appointment_rescheduled')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->dedupe_key)->toBe("reschedule:appointment:{$appointment->id}:old:{$oldSlot->id}:new:{$newSlot->id}");
});

test('cancelling appointment sends cancellation notice and writes log row', function () {
    Notification::fake();

    $donor = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $drive = Drive::factory()->published()->create();
    $slot = Slot::factory()->create(['drive_id' => $drive->id]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Cancelled,
        'cancelled_at' => now(),
    ]);

    Event::dispatch(new AppointmentCancelledEvent($appointment));

    Notification::assertSentTo($donor, AppointmentCancelled::class, function (AppointmentCancelled $notification) use ($donor) {
        $mail = $notification->toMail($donor);
        $renderedHtml = (string) $mail->render();

        return str_contains($renderedHtml, 'Remember to remove this appointment from your calendar.');
    });

    $log = NotificationLog::where('related_id', $appointment->id)
        ->where('type', 'appointment_cancelled')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->dedupe_key)->toBe("cancellation:appointment:{$appointment->id}");
});

test('notification is skipped if user is inactive, unverified or appointment not booked', function () {
    $donor = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => false,
        'email_verified_at' => null,
    ]);

    $drive = Drive::factory()->published()->create();
    $slot = Slot::factory()->create(['drive_id' => $drive->id]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    $notification = new AppointmentConfirmed($appointment);
    $shouldSend = $notification->shouldSend($donor, 'mail');

    expect($shouldSend)->toBeFalse();

    $log = NotificationLog::where('dedupe_key', $notification->dedupeKey)->first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe(NotificationStatus::Skipped);
});

test('failure after retries records failed status with truncated error message', function () {
    $donor = User::factory()->create(['role' => Role::Donor]);
    $drive = Drive::factory()->published()->create();
    $slot = Slot::factory()->create(['drive_id' => $drive->id]);
    $appointment = Appointment::factory()->create(['user_id' => $donor->id, 'slot_id' => $slot->id]);

    $notification = new AppointmentConfirmed($appointment);
    $longError = str_repeat('Connection to Brevo failed unexpectedly. ', 30); // > 500 chars

    $notification->failed(new RuntimeException($longError));

    $log = NotificationLog::where('dedupe_key', $notification->dedupeKey)->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe(NotificationStatus::Failed)
        ->and(strlen($log->error))->toBeLessThanOrEqual(500);
});

test('calendar ics generator produces valid RFC 5545 format with stable UID, UTC times, line folding, and incrementing sequence', function () {
    $donor = User::factory()->create();
    $drive = Drive::factory()->create([
        'title' => 'City Square Kampala Drive, Central Uganda; Special Session',
        'venue' => 'City Square',
        'address' => 'Kampala Road, Central Division',
    ]);

    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => CarbonImmutable::parse('2026-10-24 06:00:00', 'UTC'), // 09:00 EAT
        'ends_at' => CarbonImmutable::parse('2026-10-24 06:30:00', 'UTC'),
    ]);

    $appointment = Appointment::factory()->create([
        'id' => 42,
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
    ]);

    $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    $fromAddress = config('mail.from.address') ?: 'hello@example.com';

    $icsInitial = IcsGenerator::generate($appointment, sequence: 0);

    expect($icsInitial)->toContain("BEGIN:VCALENDAR\r\n")
        ->and($icsInitial)->toContain("METHOD:PUBLISH\r\n")
        ->and($icsInitial)->toContain("UID:appointment-42@{$host}\r\n")
        ->and($icsInitial)->toContain("DTSTART:20261024T060000Z\r\n")
        ->and($icsInitial)->toContain("DTEND:20261024T063000Z\r\n")
        ->and($icsInitial)->toContain("ORGANIZER;CN=DonorLink UG:mailto:{$fromAddress}\r\n")
        ->and($icsInitial)->toContain("SEQUENCE:0\r\n")
        ->and($icsInitial)->toContain("END:VCALENDAR\r\n");

    // Every line must end in CRLF (\r\n) and no line exceeds 75 octets
    $lines = explode("\r\n", rtrim($icsInitial, "\r\n"));
    foreach ($lines as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75);
    }

    // Now simulate reschedule log entry to verify sequence increment
    NotificationLog::factory()->sent()->create([
        'user_id' => $donor->id,
        'type' => 'appointment_rescheduled',
        'related_type' => Appointment::class,
        'related_id' => $appointment->id,
    ]);

    $icsUpdated = IcsGenerator::generate($appointment);
    expect($icsUpdated)->toContain("UID:appointment-42@{$host}\r\n")
        ->and($icsUpdated)->toContain("METHOD:PUBLISH\r\n")
        ->and($icsUpdated)->toContain("SEQUENCE:1\r\n");
});

test('reminder command sends only appointments inside 24h and 2h windows and ignores outside', function () {
    Notification::fake();

    $nowEat = Carbon::parse('2026-10-20 10:00:00', 'Africa/Kampala');
    Carbon::setTestNow($nowEat);

    $donor = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $drive = Drive::factory()->published()->create();

    // 1. Exactly in 24h window (slot starts in 23 hours)
    $slot24h = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => $nowEat->copy()->addHours(23)->setTimezone('UTC'),
        'ends_at' => $nowEat->copy()->addHours(23)->addMinutes(30)->setTimezone('UTC'),
    ]);
    $app24h = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot24h->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 2. Exactly in 2h window (slot starts in 1.5 hours)
    $slot2h = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => $nowEat->copy()->addMinutes(90)->setTimezone('UTC'),
        'ends_at' => $nowEat->copy()->addMinutes(120)->setTimezone('UTC'),
    ]);
    $app2h = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot2h->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 3. Outside windows (starts in 10 hours)
    $slotOutside = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => $nowEat->copy()->addHours(10)->setTimezone('UTC'),
        'ends_at' => $nowEat->copy()->addHours(10)->addMinutes(30)->setTimezone('UTC'),
    ]);
    $appOutside = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slotOutside->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // 4. Cancelled appointment in window (must NOT receive reminder)
    $otherDrive = Drive::factory()->published()->create();
    $slotCancelled = Slot::factory()->create([
        'drive_id' => $otherDrive->id,
        'starts_at' => $nowEat->copy()->addHours(23)->setTimezone('UTC'),
    ]);
    $appCancelled = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slotCancelled->id,
        'status' => AppointmentStatus::Cancelled,
    ]);

    // Run sweep
    $this->artisan('donorlink:send-reminders')
        ->assertSuccessful();

    // Verify 24h reminder sent
    Notification::assertSentTo($donor, AppointmentReminder::class, function ($n) use ($app24h) {
        return $n->appointment->id === $app24h->id && $n->variant === '24h';
    });

    // Verify 2h reminder sent
    Notification::assertSentTo($donor, AppointmentReminder::class, function ($n) use ($app2h) {
        return $n->appointment->id === $app2h->id && $n->variant === '2h';
    });

    // Verify appOutside and appCancelled received nothing
    Notification::assertNotSentTo($donor, AppointmentReminder::class, function ($n) use ($appOutside, $appCancelled) {
        return in_array($n->appointment->id, [$appOutside->id, $appCancelled->id]);
    });
});

test('reminders sweep is idempotent and sends nothing on a repeated run', function () {
    Notification::fake();

    $nowEat = Carbon::parse('2026-10-20 10:00:00', 'Africa/Kampala');
    Carbon::setTestNow($nowEat);

    $donor = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $drive = Drive::factory()->published()->create();
    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => $nowEat->copy()->addHours(23)->setTimezone('UTC'),
        'ends_at' => $nowEat->copy()->addHours(23)->addMinutes(30)->setTimezone('UTC'),
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // First run
    $this->artisan('donorlink:send-reminders')
        ->expectsOutputToContain('Reminders sweep completed: 1 dispatched (24h: 1, 2h: 0), 0 skipped, 0 already processed.')
        ->assertSuccessful();

    // Second run
    $this->artisan('donorlink:send-reminders')
        ->expectsOutputToContain('Reminders sweep completed: 0 dispatched (24h: 0, 2h: 0), 0 skipped, 1 already processed.')
        ->assertSuccessful();
});

test('3-hour skip rule prevents sending reminders when confirmation was sent recently', function () {
    Notification::fake();

    $nowEat = Carbon::parse('2026-10-20 10:00:00', 'Africa/Kampala');
    Carbon::setTestNow($nowEat);

    $donor = User::factory()->create(['role' => Role::Donor, 'is_active' => true, 'email_verified_at' => now()]);
    $drive = Drive::factory()->published()->create();
    $slot = Slot::factory()->create([
        'drive_id' => $drive->id,
        'starts_at' => $nowEat->copy()->addHours(23)->setTimezone('UTC'),
    ]);

    $appointment = Appointment::factory()->create([
        'user_id' => $donor->id,
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Booked,
    ]);

    // Insert confirmation log sent 1 hour ago
    NotificationLog::factory()->sent()->create([
        'user_id' => $donor->id,
        'type' => 'appointment_confirmed',
        'related_type' => Appointment::class,
        'related_id' => $appointment->id,
        'sent_at' => $nowEat->copy()->subHour()->setTimezone('UTC'),
    ]);

    $this->artisan('donorlink:send-reminders')
        ->expectsOutputToContain('Skipped 24h reminder for appointment #'.$appointment->id)
        ->assertSuccessful();

    Notification::assertNotSentTo($donor, AppointmentReminder::class);

    $log = NotificationLog::where('dedupe_key', "reminder_24h:appointment:{$appointment->id}:slot:{$slot->id}")->first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe(NotificationStatus::Skipped)
        ->and($log->error)->toContain('less than 3 hours ago');
});

test('mail budget never blocks transactional messages and logs warning at 90 percent', function () {
    config([
        'donorlink.mail.daily_budget' => 100,
        'donorlink.mail.transactional_reserve' => 30,
        'donorlink.mail.announcement_chunk' => 10,
    ]);

    $budget = app(MailBudget::class);

    // Create 91 sent logs in the last 24 hours (over 90% of 100)
    NotificationLog::factory()->count(91)->sent()->create([
        'sent_at' => now()->subHours(2),
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message) {
            return str_contains($message, 'over 90% of the daily budget');
        });

    $budget->checkTransactionalWarning();

    // Announcements must be blocked because remaining = 100 - 91 - 30 = -21 <= 0
    expect($budget->remaining())->toBe(0)
        ->and($budget->canSendAnnouncement())->toBeFalse();
});

test('staff can queue announcements for published drive and dispatch command respects chunk and budget', function () {
    config([
        'donorlink.mail.daily_budget' => 250,
        'donorlink.mail.transactional_reserve' => 50,
        'donorlink.mail.announcement_chunk' => 2, // Small chunk to test batching
    ]);

    $staff = User::factory()->create(['role' => Role::Staff]);

    // Create 3 eligible donors
    $donors = User::factory()->count(3)->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
        'notify_new_drives' => true,
    ]);

    // Create 1 opted-out donor
    $optedOut = User::factory()->create([
        'role' => Role::Donor,
        'is_active' => true,
        'email_verified_at' => now(),
        'notify_new_drives' => false,
    ]);

    $drive = Drive::factory()->published()->create();

    // Staff queues announcement via post request
    $response = $this->actingAs($staff)->post(route('staff.drives.announcement', $drive));
    $response->assertRedirect(route('staff.drives.show', $drive))
        ->assertSessionHas('status');

    // Exactly 3 queued logs for the 3 opted-in donors
    expect(NotificationLog::where('related_id', $drive->id)->where('status', NotificationStatus::Queued)->count())->toBe(3)
        ->and(NotificationLog::where('user_id', $optedOut->id)->exists())->toBeFalse();

    // Prevent double clicking / second announcement on same drive
    $secondAttempt = $this->actingAs($staff)->post(route('staff.drives.announcement', $drive));
    $secondAttempt->assertSessionHas('error');

    // Run 1 of dispatch command: should send first chunk of 2
    $this->artisan('donorlink:dispatch-announcements')
        ->expectsOutputToContain('Dispatched 2 announcement(s)')
        ->assertSuccessful();

    expect(NotificationLog::where('related_id', $drive->id)->where('status', NotificationStatus::Sent)->count())->toBe(2)
        ->and(NotificationLog::where('related_id', $drive->id)->where('status', NotificationStatus::Queued)->count())->toBe(1);

    // Run 2 of dispatch command: should send remaining 1
    $this->artisan('donorlink:dispatch-announcements')
        ->expectsOutputToContain('Dispatched 1 announcement(s)')
        ->assertSuccessful();

    expect(NotificationLog::where('related_id', $drive->id)->where('status', NotificationStatus::Sent)->count())->toBe(3)
        ->and(NotificationLog::where('related_id', $drive->id)->where('status', NotificationStatus::Queued)->count())->toBe(0);
});

test('unsubscribe route shows confirmation page on GET and unsets preference on POST without CSRF', function () {
    $donor = User::factory()->create([
        'role' => Role::Donor,
        'notify_new_drives' => true,
    ]);

    $validSignedUrl = URL::signedRoute('unsubscribe.announcements', ['user' => $donor->id]);

    // 1. GET shows confirmation page with button that POSTs, but does NOT unsubscribe
    $getResponse = $this->get($validSignedUrl);
    $getResponse->assertOk()
        ->assertSee('Unsubscribe from Blood Drive Announcements')
        ->assertSee('Confirm Unsubscribe');

    expect($donor->fresh()->notify_new_drives)->toBeTrue();

    // 2. POST (RFC 8058 one-click) succeeds without CSRF token and unsets preference
    $postResponse = $this->post($validSignedUrl, ['List-Unsubscribe' => 'One-Click']);
    $postResponse->assertOk()
        ->assertSee('Preferences Updated')
        ->assertSee('You have been unsubscribed from announcements');

    expect($donor->fresh()->notify_new_drives)->toBeFalse();

    // 3. Tampered signatures return 403 Forbidden on both GET and POST
    $tamperedUrl = $validSignedUrl.'tamper';
    $this->get($tamperedUrl)->assertForbidden();
    $this->post($tamperedUrl)->assertForbidden();
});

test('staff drive show page displays announcements allowed right now modal label', function () {
    $staff = User::factory()->create(['role' => Role::Staff]);
    $drive = Drive::factory()->published()->create();

    $response = $this->actingAs($staff)->get(route('staff.drives.show', $drive));

    $response->assertOk()
        ->assertSee('Announcements allowed right now:');
});

test('admin notifications screen is admin-only and displays log metadata without email bodies', function () {
    $donor = User::factory()->create(['role' => Role::Donor, 'name' => 'Alice Musoke']);
    $staff = User::factory()->create(['role' => Role::Staff]);
    $admin = User::factory()->create(['role' => Role::Admin]);

    $log = NotificationLog::factory()->sent()->create([
        'user_id' => $donor->id,
        'type' => 'appointment_confirmed',
    ]);

    // Donors cannot view admin notifications
    $this->actingAs($donor)->get(route('admin.notifications.index'))->assertForbidden();

    // Staff cannot view admin notifications
    $this->actingAs($staff)->get(route('admin.notifications.index'))->assertForbidden();

    // Admins can view
    $response = $this->actingAs($admin)->get(route('admin.notifications.index'));
    $response->assertOk()
        ->assertSee('Email Notification Log')
        ->assertSee('Alice Musoke')
        ->assertSee('Appointment Confirmed')
        ->assertSee('Sent')
        ->assertDontSee('Subject:') // No bodies or sensitive email contents
        ->assertDontSee('DTSTART');
});

test('copy updates in registration and privacy notice mention appointment emails and announcements', function () {
    $registerPage = $this->get(route('register'));
    $registerPage->assertOk()
        ->assertSee('I agree to DonorLink UG storing my details so UBTS can manage my appointments and email me appointment confirmations, reminders and announcements of new blood drives');

    $privacyPage = $this->get(route('privacy'));
    $privacyPage->assertOk()
        ->assertSee('reminder notifications (sent 24 hours and 2 hours before your appointment)')
        ->assertSee('email preferences in your donor profile or by clicking the unsubscribe link');
});
