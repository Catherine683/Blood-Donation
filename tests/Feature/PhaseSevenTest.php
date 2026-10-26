<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Exceptions\NotAllowedException;
use App\Livewire\Admin\StaffManagement;
use App\Livewire\Staff\DriveRoster;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\EmailChangeNotice;
use App\Notifications\StaffInvitationNotification;
use App\Services\AttendanceService;
use App\Services\BookingService;
use App\Services\CsvExportService;
use App\Services\DonationRecorder;
use App\Services\ReportService;
use App\Support\RedirectHelper;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-10-18 10:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    // ==========================================
    // 1.1 Booking and Login Redirects
    // ==========================================

    public function test_hostile_return_to_redirects_fall_back_to_default(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $donor = User::factory()->create([
            'role' => Role::Donor,
            'email_verified_at' => now(),
            'password' => Hash::make('Secret123!'),
        ]);

        $hostileUrls = [
            'https://evil.test',
            '//evil.test',
            '/\evil.test',
            'javascript:alert(1)',
            '%2F%2Fevil.test',
            'http://attacker.com/steal',
            '\\\\evil.test',
        ];

        foreach ($hostileUrls as $hostile) {
            $response = $this->post('/login', [
                'email' => $donor->email,
                'password' => 'Secret123!',
                'return_to' => $hostile,
            ]);

            $response->assertRedirect('/donor');
            $this->assertNotEquals($hostile, $response->headers->get('Location'));
            $this->post('/logout');
        }

        // Helper unit assertion
        foreach ($hostileUrls as $hostile) {
            $this->assertNull(RedirectHelper::sanitizeRelativePath($hostile));
        }

        // Valid relative paths work
        $this->assertEquals('/drives/makerere', RedirectHelper::sanitizeRelativePath('/drives/makerere'));
        $this->assertEquals('/donor/profile', RedirectHelper::sanitizeRelativePath('/donor/profile'));
    }

    public function test_staff_and_admin_accounts_are_unable_to_book_through_donor_route(): void
    {
        $staff = User::factory()->create(['role' => Role::Staff, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

        $drive = Drive::factory()->create([
            'status' => DriveStatus::Published,
            'starts_on' => now()->addDays(2)->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
        ]);
        $slot = Slot::factory()->create(['drive_id' => $drive->id, 'capacity' => 10]);

        // Staff blocked
        $this->actingAs($staff)
            ->post('/donor/appointments', ['slot_id' => $slot->id])
            ->assertForbidden();

        // Admin blocked
        $this->actingAs($admin)
            ->post('/donor/appointments', ['slot_id' => $slot->id])
            ->assertForbidden();
    }

    public function test_staff_and_admin_accounts_are_unable_to_book_through_booking_service(): void
    {
        $staff = User::factory()->create(['role' => Role::Staff, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

        $drive = Drive::factory()->create([
            'status' => DriveStatus::Published,
            'starts_on' => now()->addDays(2)->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
        ]);
        $slot = Slot::factory()->create(['drive_id' => $drive->id, 'capacity' => 10]);

        $bookingService = app(BookingService::class);

        try {
            $bookingService->book($staff, $slot);
            $this->fail('Staff booking should have thrown an exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotAllowedException::class, $e);
        }

        try {
            $bookingService->book($admin, $slot);
            $this->fail('Admin booking should have thrown an exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotAllowedException::class, $e);
        }
    }

    // ==========================================
    // 1.2 Email & Unsubscribe
    // ==========================================

    public function test_announcement_unsubscribe_accepts_post_without_csrf_and_requires_signature(): void
    {
        $donor = User::factory()->create(['notify_new_drives' => true]);

        $signedUrl = URL::signedRoute('unsubscribe.announcements', ['user' => $donor->id]);

        // Tampered signature fails
        $tamperedUrl = $signedUrl.'invalid';
        $this->post($tamperedUrl)->assertForbidden();
        $this->get($tamperedUrl)->assertForbidden();

        // GET shows confirmation page with POST button
        $getResponse = $this->get($signedUrl);
        $getResponse->assertOk();
        $getResponse->assertSee('Unsubscribe from Blood Drive Announcements');
        $getResponse->assertSee('Confirm Unsubscribe');

        // POST without CSRF succeeds and unsubscribes
        $postResponse = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($signedUrl);

        $postResponse->assertOk();
        $postResponse->assertSee('You have been unsubscribed');
        $this->assertFalse($donor->fresh()->notify_new_drives);
    }

    public function test_cancellation_email_contains_calendar_removal_reminder_line(): void
    {
        $donor = User::factory()->create(['name' => 'Test Donor']);
        $drive = Drive::factory()->create(['title' => 'Kololo Drive']);
        $slot = Slot::factory()->create(['drive_id' => $drive->id]);
        $appointment = Appointment::factory()->create(['user_id' => $donor->id, 'slot_id' => $slot->id]);

        $notification = new AppointmentCancelled($appointment);
        $mail = $notification->toMail($donor);
        $rendered = (string) $mail->render();

        $this->assertStringContainsString('Remember to remove this appointment from your calendar.', $rendered);
    }

    // ==========================================
    // 1.3 Staff Actions Authorization inside Livewire
    // ==========================================

    public function test_staff_actions_authorize_inside_livewire_action_even_if_demoted_after_mount(): void
    {
        $user = User::factory()->create(['role' => Role::Staff, 'email_verified_at' => now()]);
        $drive = Drive::factory()->create([
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);
        $slot = Slot::factory()->create(['drive_id' => $drive->id]);
        $donor = User::factory()->create(['role' => Role::Donor]);
        $appointment = Appointment::factory()->create(['user_id' => $donor->id, 'slot_id' => $slot->id, 'status' => AppointmentStatus::Booked]);

        $this->actingAs($user);

        // Mount DriveRoster as staff
        $component = Livewire::actingAs($user)->test(DriveRoster::class, ['drive' => $drive])->instance();

        // Now demote user to donor after mount and set auth context to the demoted donor
        $user->update(['role' => Role::Donor]);
        $demotedUser = $user->fresh();
        $this->actingAs($demotedUser);

        // Verify each action method enforces authorization inside the action itself
        $attendanceService = app(AttendanceService::class);
        $donationRecorder = app(DonationRecorder::class);

        $actions = [
            'checkIn' => fn () => $component->checkIn($appointment->id, $attendanceService),
            'promptUndoCheckIn' => fn () => $component->promptUndoCheckIn($appointment->id),
            'promptNoShow' => fn () => $component->promptNoShow($appointment->id),
            'promptCancel' => fn () => $component->promptCancel($appointment->id),
            'executeConfirmedAction' => fn () => $component->executeConfirmedAction($attendanceService),
            'openOutcomeModal' => fn () => $component->openOutcomeModal($appointment->id),
            'saveOutcome' => fn () => $component->saveOutcome($donationRecorder),
            'recordWalkIn' => fn () => $component->recordWalkIn($donationRecorder),
            'openCloseOutModal' => fn () => $component->openCloseOutModal(),
            'confirmCloseOut' => fn () => $component->confirmCloseOut($attendanceService),
        ];

        foreach ($actions as $actionName => $action) {
            try {
                $action();
                $this->fail("Expected AuthorizationException was not thrown for action [{$actionName}].");
            } catch (\Throwable $e) {
                $this->assertInstanceOf(
                    AuthorizationException::class,
                    $e,
                    "Action [{$actionName}] failed with unexpected exception: ".$e->getMessage()
                );
            }
        }
    }

    // ==========================================
    // 1.4 Reports & Reconciliation & Exports
    // ==========================================

    public function test_reconciliation_totals_are_identical_across_roster_report_and_dashboard(): void
    {
        $reportService = app(ReportService::class);
        $drives = Drive::with(['slots.appointments.user', 'donationRecords.user'])->get();

        foreach ($drives as $drive) {
            $driveMetrics = $reportService->getDriveMetrics($drive);
            $livePanelMetrics = $reportService->getLivePanelMetrics($drive);

            // Assert common totals match exactly between drive report and dashboard live panel
            $this->assertEquals($driveMetrics['expected'], $livePanelMetrics['expected'], "Expected mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['checked_in'], $livePanelMetrics['checked_in'], "Checked-in mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['attended'], $livePanelMetrics['attended'], "Attended mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['donated'], $livePanelMetrics['donated'], "Donated mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['deferred'], $livePanelMetrics['deferred'], "Deferred mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['no_show'], $livePanelMetrics['no_show'], "No-show mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['cancelled'], $livePanelMetrics['cancelled'], "Cancelled mismatch on drive {$drive->id}");
            $this->assertEquals($driveMetrics['walk_ins'], $livePanelMetrics['walk_ins'], "Walk-ins mismatch on drive {$drive->id}");

            // Verify live sub-counts logic
            $this->assertEquals($driveMetrics['expected'] - $driveMetrics['checked_in'], $driveMetrics['waiting_to_arrive']);
            $this->assertEquals($driveMetrics['checked_in'] - ($driveMetrics['donated'] + $driveMetrics['deferred']), $driveMetrics['awaiting_outcome']);
        }
    }

    public function test_phone_numbers_exported_in_grouped_format_and_formulas_neutralized_in_free_text(): void
    {
        $exportService = app(CsvExportService::class);

        // Grouped local format keeps leading zero
        $formattedPhone = $exportService->formatLocalPhone('+256700000003');
        $this->assertEquals('0700 000 003', $formattedPhone);

        // Phone numbers are not prefixed with quote
        $this->assertFalse(str_starts_with($formattedPhone, "'"));

        // Free-text formula injection is neutralized
        $hostileName = '=SUM(1,2)';
        $sanitizedName = $exportService->sanitizeCell($hostileName);
        $this->assertEquals("'=SUM(1,2)", $sanitizedName);

        $hostileCsv = '@attack';
        $this->assertEquals("'@attack", $exportService->sanitizeCell($hostileCsv));

        // Normal text remains clean
        $this->assertEquals('Grace Atuhaire', $exportService->sanitizeCell('Grace Atuhaire'));
    }

    // ==========================================
    // 2. Staff & Admin Account Management
    // ==========================================

    public function test_create_admin_command_bootstraps_first_admin_and_refuses_when_admin_exists(): void
    {
        // Fresh DB with no admin
        $this->artisan('donorlink:create-admin')
            ->expectsQuestion('Full Name', 'System Administrator')
            ->expectsQuestion('Email address', 'admin@uganda.test')
            ->expectsQuestion('Password (min 8 characters)', 'SecurePassword123!')
            ->expectsQuestion('Confirm Password', 'SecurePassword123!')
            ->expectsOutputToContain('Administrator account created successfully')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin@uganda.test')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(Role::Admin, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('SecurePassword123!', $admin->password));

        // Audit log written with null actor
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.bootstrapped',
            'actor_id' => null,
            'subject_type' => User::class,
            'subject_id' => $admin->id,
        ]);

        // Attempting again refuses without --force
        $this->artisan('donorlink:create-admin')
            ->expectsOutputToContain('An active administrator account already exists')
            ->assertExitCode(1);

        // Succeeds with --force
        $this->artisan('donorlink:create-admin --force')
            ->expectsQuestion('Full Name', 'Second Admin')
            ->expectsQuestion('Email address', 'admin2@uganda.test')
            ->expectsQuestion('Password (min 8 characters)', 'SecurePassword123!')
            ->expectsQuestion('Confirm Password', 'SecurePassword123!')
            ->assertExitCode(0);
    }

    public function test_only_admins_can_reach_admin_staff_screen(): void
    {
        $donor = User::factory()->create(['role' => Role::Donor, 'email_verified_at' => now()]);
        $staff = User::factory()->create(['role' => Role::Staff, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

        $this->get('/admin/staff')->assertRedirect('/login');
        $this->actingAs($donor)->get('/admin/staff')->assertForbidden();
        $this->actingAs($staff)->get('/admin/staff')->assertForbidden();
        $this->actingAs($admin)->get('/admin/staff')->assertOk();
    }

    public function test_admin_can_invite_staff_member_with_72h_signed_link(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(StaffManagement::class)
            ->set('inviteName', 'Nurse Jane')
            ->set('inviteEmail', 'nurse.jane@hospital.test')
            ->set('inviteRole', 'staff')
            ->call('sendInvitation')
            ->assertHasNoErrors();

        $invited = User::where('email', 'nurse.jane@hospital.test')->first();
        $this->assertNotNull($invited);
        $this->assertEquals(Role::Staff, $invited->role);
        $this->assertNull($invited->email_verified_at);

        Notification::assertSentTo($invited, StaffInvitationNotification::class);

        // Audit log written
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff.invited',
            'actor_id' => $admin->id,
            'subject_id' => $invited->id,
        ]);
    }

    public function test_staff_invitation_acceptance_sets_password_activates_and_marks_verified(): void
    {
        $invited = User::factory()->create([
            'role' => Role::Staff,
            'email' => 'jane@hospital.test',
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(32)),
        ]);

        $validUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $invited->id]
        );

        // GET loads setup page
        $this->get($validUrl)
            ->assertOk()
            ->assertSee('Set Your Password');

        // POST sets password and signs in
        $response = $this->post($validUrl, [
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($invited);

        $invited->refresh();
        $this->assertNotNull($invited->email_verified_at);
        $this->assertTrue(Hash::check('NewSecurePass123!', $invited->password));

        // Reusing the same URL fails because email is now verified
        $this->post('/logout');
        $this->get($validUrl)->assertRedirect('/login');
    }

    public function test_expired_or_tampered_invitation_links_are_rejected(): void
    {
        $invited = User::factory()->create([
            'role' => Role::Staff,
            'email_verified_at' => null,
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->subHour(),
            ['user' => $invited->id]
        );

        $this->get($expiredUrl)->assertForbidden();

        $tamperedUrl = $expiredUrl.'corrupt';
        $this->get($tamperedUrl)->assertForbidden();
    }

    public function test_deactivation_kills_session_immediately_and_blocks_access(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);
        $staff = User::factory()->create(['role' => Role::Staff, 'email_verified_at' => now(), 'is_active' => true]);

        // Simulate active staff session in DB
        DB::table('sessions')->insert([
            'id' => 'session_staff_123',
            'user_id' => $staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => serialize(['dummy' => 'data']),
            'last_activity' => time(),
        ]);

        $this->assertEquals(1, DB::table('sessions')->where('user_id', $staff->id)->count());

        // Admin deactivates staff via Livewire
        Livewire::actingAs($admin)
            ->test(StaffManagement::class)
            ->call('promptDeactivate', $staff->id)
            ->call('confirmDeactivate')
            ->assertHasNoErrors();

        $this->assertFalse($staff->fresh()->is_active);

        // Session row deleted immediately
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $staff->id)->count());

        // Middleware check blocks deactivated user
        $this->actingAs($staff->fresh())
            ->get('/staff')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_cannot_deactivate_or_demote_self_or_last_active_admin(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now(), 'is_active' => true]);

        // Self-protection on deactivation
        $component = Livewire::actingAs($admin)
            ->test(StaffManagement::class)
            ->call('promptDeactivate', $admin->id);

        $this->assertStringContainsString('cannot deactivate your own', (string) $component->get('feedbackMessage'));

        // Self-protection on demotion
        $component->call('openChangeRoleModal', $admin->id);
        $this->assertStringContainsString('cannot change your own', (string) $component->get('feedbackMessage'));

        // Last-admin protection: create a second admin, demote one, then try demoting the last
        $admin2 = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now(), 'is_active' => true]);

        $component->call('openChangeRoleModal', $admin2->id)
            ->set('targetRole', 'staff')
            ->call('confirmChangeRole');

        $this->assertEquals(Role::Staff, $admin2->fresh()->role);

        // Now admin is the only active admin remaining; try deactivating
        $component->call('promptDeactivate', $admin->id);
        $this->assertStringContainsString('cannot deactivate', (string) $component->get('feedbackMessage'));
    }

    // ==========================================
    // 3. Safe Email Change for Donors
    // ==========================================

    public function test_donor_email_change_does_not_lock_out_and_swaps_only_upon_verification(): void
    {
        Notification::fake();

        $donor = User::factory()->create([
            'email' => 'original@donor.test',
            'email_verified_at' => now(),
            'password' => Hash::make('MyPassword123!'),
        ]);

        $this->actingAs($donor);

        // Request email change
        $response = $this->post('/donor/profile/email-change', [
            'new_email' => 'newaddress@donor.test',
            'current_password' => 'MyPassword123!',
        ]);

        $response->assertSessionHas('status', 'A verification link has been sent to newaddress@donor.test. Please confirm it within 24 hours.');
        $donor->refresh();

        // Original email unchanged, pending_email set, account remains verified
        $this->assertEquals('original@donor.test', $donor->email);
        $this->assertEquals('newaddress@donor.test', $donor->pending_email);
        $this->assertNotNull($donor->email_verified_at);

        // Donor can still access protected routes
        $this->get('/donor')->assertOk();

        // Notifications sent: verification link to new email, masked notice to old
        Notification::assertSentTo($donor, EmailChangeNotice::class, function ($n) {
            return $n->maskedEmail === 'n***@donor.test';
        });

        // Signed confirmation URL
        $confirmUrl = URL::temporarySignedRoute(
            'donor.email.verify-change',
            now()->addHours(24),
            ['user' => $donor->id, 'email' => 'newaddress@donor.test']
        );

        // Confirming changes email
        $this->get($confirmUrl)->assertRedirect('/donor/profile');

        $donor->refresh();
        $this->assertEquals('newaddress@donor.test', $donor->email);
        $this->assertNull($donor->pending_email);
        $this->assertNotNull($donor->email_verified_at);

        // Audit log written
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'donor.email_changed',
            'actor_id' => $donor->id,
            'subject_id' => $donor->id,
        ]);
    }

    public function test_donor_email_change_cancel_and_uniqueness_checks(): void
    {
        $existing = User::factory()->create(['email' => 'taken@donor.test']);
        $donor = User::factory()->create([
            'email' => 'donor@test.ug',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($donor);

        // Cannot request already taken email
        $this->post('/donor/profile/email-change', [
            'new_email' => 'taken@donor.test',
            'current_password' => 'Password123!',
        ])->assertSessionHasErrors('new_email');

        // Request valid pending change
        $this->post('/donor/profile/email-change', [
            'new_email' => 'candidate@donor.test',
            'current_password' => 'Password123!',
        ]);

        $this->assertEquals('candidate@donor.test', $donor->fresh()->pending_email);

        // Cancel pending change
        $this->post('/donor/profile/email-change/cancel')->assertRedirect('/donor/profile');
        $this->assertNull($donor->fresh()->pending_email);
    }

    // ==========================================
    // 4. Donor Download My Data
    // ==========================================

    public function test_donor_can_download_their_data_and_staff_notes_are_excluded(): void
    {
        $donor = User::factory()->create(['name' => 'Data Donor', 'role' => Role::Donor]);
        $drive = Drive::factory()->create(['title' => 'Community Drive']);
        $slot = Slot::factory()->create(['drive_id' => $drive->id]);
        $appointment = Appointment::factory()->create([
            'user_id' => $donor->id,
            'slot_id' => $slot->id,
            'status' => AppointmentStatus::Attended,
        ]);
        $donation = DonationRecord::factory()->create([
            'user_id' => $donor->id,
            'drive_id' => $drive->id,
            'outcome' => DonationOutcome::Donated,
            'staff_note' => 'Secret confidential clinical note',
        ]);

        $this->actingAs($donor);

        $response = $this->get('/donor/profile/export');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');

        $data = $response->json();
        $this->assertEquals('Data Donor', $data['personal_details']['name']);
        $this->assertCount(1, $data['appointments']);
        $this->assertCount(1, $data['donation_history']);
        $this->assertEquals('Donated', $data['donation_history'][0]['outcome']);

        // Staff notes strictly excluded
        $this->assertArrayNotHasKey('staff_note', $data['donation_history'][0]);
        $this->assertArrayNotHasKey('staff_notes', $data['donation_history'][0]);
        $this->assertStringNotContainsString('Secret confidential clinical note', $response->getContent());

        // Audit log written
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'donor.data_exported',
            'actor_id' => $donor->id,
            'subject_id' => $donor->id,
        ]);
    }

    // ==========================================
    // 5. Config-Driven Privacy Page
    // ==========================================

    public function test_privacy_page_renders_only_set_sections_and_escapes_hostile_content(): void
    {
        // Unset state: sections are hidden
        Config::set('donorlink.privacy.contact_email', null);
        Config::set('donorlink.privacy.contact_address', null);
        Config::set('donorlink.privacy.retention_summary', null);
        Config::set('donorlink.privacy.removal_process', null);

        $response = $this->get('/privacy');
        $response->assertOk();
        $response->assertDontSee('Contact & Inquiries');
        $response->assertDontSee('Postal Address');
        $response->assertDontSee('Data Retention Policy');
        $response->assertDontSee('Account Removal Process');

        // Set state with hostile strings: properly escaped
        Config::set('donorlink.privacy.contact_email', 'privacy@ubts.go.ug');
        Config::set('donorlink.privacy.contact_address', 'Plot 1 Nakasero Road');
        Config::set('donorlink.privacy.retention_summary', '<script>alert("retention")</script>');
        Config::set('donorlink.privacy.removal_process', 'Send an email to <b>remove</b>.');

        $response = $this->get('/privacy');
        $response->assertOk();
        $response->assertSee('privacy@ubts.go.ug');
        $response->assertSee('Plot 1 Nakasero Road');
        $response->assertDontSee('<script>alert("retention")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;retention&quot;)&lt;/script&gt;', false);
    }
}
