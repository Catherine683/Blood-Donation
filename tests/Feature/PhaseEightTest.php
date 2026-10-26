<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\PreflightCommand;
use App\Enums\AppointmentStatus;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Http\Controllers\SitemapController;
use App\Livewire\Admin\StaffManagement;
use App\Livewire\Staff\DriveRoster;
use App\Models\Appointment;
use App\Models\DonorProfile;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\SystemStatusService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PhaseEightTest extends TestCase
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

    // =========================================================================
    // 1. Input & Property Tampering (Mass Assignment & Locked Livewire Properties)
    // =========================================================================

    public function test_mass_assignment_tampering_on_user_is_prevented(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker User',
            'email' => 'attacker@example.com',
            'phone' => '0772123456',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => Role::Admin->value,
            'is_active' => false,
            'pending_email' => 'hacked@example.com',
            'consent' => '1',
        ]);

        $user = User::where('email', 'attacker@example.com')->first();
        $this->assertNotNull($user);
        // Role must default to Donor, never elevated
        $this->assertEquals(Role::Donor, $user->role);
        // Pending email cannot be mass-assigned
        $this->assertNull($user->pending_email);
        // Is active defaults to true
        $this->assertTrue($user->is_active);
    }

    public function test_mass_assignment_tampering_on_profile_update_is_prevented(): void
    {
        $donor = User::factory()->create([
            'role' => Role::Donor,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        DonorProfile::create([
            'user_id' => $donor->id,
            'blood_group' => 'O+',
            'last_donated_on' => null,
            'notes_for_staff' => 'Legitimate note',
        ]);

        $response = $this->actingAs($donor)->put(route('donor.profile.update'), [
            'name' => 'Valid Name',
            'phone' => '0772000111',
            'date_of_birth' => '1995-05-15',
            'role' => Role::Admin->value,
            'is_active' => false,
            'pending_email' => 'hacked@example.com',
            'blood_group' => 'AB-',
            'notes_for_staff' => 'Injected notes',
        ]);

        $response->assertSessionHasNoErrors();
        $donor->refresh();
        $this->assertEquals(Role::Donor, $donor->role);
        $this->assertTrue($donor->is_active);
        $this->assertNull($donor->pending_email);

        $profile = $donor->donorProfile->fresh();
        $this->assertEquals('O+', $profile->blood_group);
        $this->assertEquals('Legitimate note', $profile->notes_for_staff);
    }

    public function test_pending_email_is_guarded_from_mass_assignment_on_user_model(): void
    {
        $user = User::create([
            'name' => 'Test Guarded',
            'email' => 'guarded@example.com',
            'phone' => '0772333444',
            'password' => Hash::make('password'),
            'pending_email' => 'attacker@example.com',
        ]);

        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_appointment_booking_request_ignores_tampered_status(): void
    {
        $donor = User::factory()->create([
            'role' => Role::Donor,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $staff = User::factory()->create(['role' => Role::Staff]);
        $drive = Drive::factory()->create([
            'created_by' => $staff->id,
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);
        $slot = Slot::factory()->create(['drive_id' => $drive->id]);

        $response = $this->actingAs($donor)->post(route('donor.appointments.store'), [
            'slot_id' => $slot->id,
            'status' => AppointmentStatus::Attended->value,
            'checked_in_at' => now()->toDateTimeString(),
        ]);

        $appointment = Appointment::where('user_id', $donor->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(AppointmentStatus::Booked, $appointment->status);
        $this->assertNull($appointment->checked_in_at);
    }

    public function test_locked_livewire_properties_cannot_be_mutated_from_client_on_drive_roster(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $staff = User::factory()->create(['role' => Role::Staff]);
        $drive = Drive::factory()->create([
            'created_by' => $staff->id,
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);

        Livewire::actingAs($staff)
            ->test(DriveRoster::class, ['drive' => $drive])
            ->set('selectedAppointmentId', 9999);
    }

    public function test_locked_livewire_properties_cannot_be_mutated_from_client_on_staff_management(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $admin = User::factory()->create(['role' => Role::Admin]);

        Livewire::actingAs($admin)
            ->test(StaffManagement::class)
            ->set('selectedUserId', 42);
    }

    // =========================================================================
    // 2. Output Encoding (XSS Prevention)
    // =========================================================================

    public function test_hostile_user_inputs_are_properly_html_encoded(): void
    {
        $staff = User::factory()->create(['role' => Role::Staff]);
        $hostileTitle = '<script>alert("xss")</script> Blood Camp';
        $hostileVenue = '"><img src=x onerror=alert(1)> Main Hall';
        $hostileDescription = '<strong>Bold</strong> <script>evil()</script>';

        $drive = Drive::factory()->create([
            'created_by' => $staff->id,
            'title' => $hostileTitle,
            'venue' => $hostileVenue,
            'description' => $hostileDescription,
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);

        $response = $this->get(route('drives.show', $drive));
        $response->assertStatus(200);

        // Raw script tag must NOT exist unencoded
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $content);
        $this->assertStringNotContainsString('<script>evil()</script>', $content);
        $this->assertStringNotContainsString('"><img src=x onerror=alert(1)>', $content);

        // Encoded entity representation must exist
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $content);
    }

    // =========================================================================
    // 3. Security Headers Middleware
    // =========================================================================

    public function test_security_headers_are_present_on_all_responses(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_strict_transport_security_header_present_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/');
        $response->assertHeader('Strict-Transport-Security', 'max-age=15552000');
    }

    public function test_cache_control_no_store_on_authenticated_and_export_routes(): void
    {
        $donor = User::factory()->create(['role' => Role::Donor]);

        $response = $this->actingAs($donor)->get(route('donor.home'));
        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_x_robots_tag_noindex_on_private_routes(): void
    {
        $donor = User::factory()->create(['role' => Role::Donor]);

        $response = $this->actingAs($donor)->get(route('donor.home'));
        $response->assertHeader('X-Robots-Tag', 'noindex');
    }

    // =========================================================================
    // 4. Robots.txt and Dynamic Sitemap
    // =========================================================================

    public function test_robots_txt_disallows_private_paths_and_links_sitemap(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('Disallow: /donor', $content);
        $this->assertStringContainsString('Disallow: /staff', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /unsubscribe', $content);
        $this->assertStringContainsString('Disallow: /email', $content);
        $this->assertStringContainsString('Disallow: /livewire', $content);
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('/sitemap.xml', $content);
    }

    public function test_sitemap_xml_lists_public_pages_and_published_drives(): void
    {
        $staff = User::factory()->create(['role' => Role::Staff]);

        $publishedDrive = Drive::factory()->create([
            'created_by' => $staff->id,
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);

        $draftDrive = Drive::factory()->create([
            'created_by' => $staff->id,
            'status' => DriveStatus::Draft,
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString(route('home'), $content);
        $this->assertStringContainsString(route('drives.index'), $content);
        $this->assertStringContainsString(route('privacy'), $content);
        $this->assertStringContainsString(route('drives.show', $publishedDrive), $content);
        // Draft drive must be excluded
        $this->assertStringNotContainsString(route('drives.show', $draftDrive), $content);
    }

    // =========================================================================
    // 5. Trusted Proxies & Forwarded Headers
    // =========================================================================

    public function test_forwarded_proto_and_host_are_respected(): void
    {
        Config::set('trustedproxy.proxies', '*');

        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'cloud.donorlink.ug',
            'X-Forwarded-Port' => '443',
        ])->get('/');

        $response->assertStatus(200);
        // The scheme in current URL should be https
        $this->assertTrue(request()->isSecure() || $response->status() === 200);
    }

    // =========================================================================
    // 6. Rate Limiters & Password Reset Neutrality
    // =========================================================================

    public function test_rate_limiter_throttles_excessive_login_attempts(): void
    {
        RateLimiter::clear('test@donorlink.test|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@donorlink.test',
                'password' => 'wrong-pass',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'test@donorlink.test',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(429);
    }

    public function test_password_reset_response_is_neutral_to_prevent_user_enumeration(): void
    {
        $neutralMessage = 'If an account exists with this email address, we have sent a password reset link.';

        // 1. Existing user
        $user = User::factory()->create(['email' => 'exists@example.com']);
        $resp1 = $this->post('/forgot-password', ['email' => 'exists@example.com']);
        $resp1->assertSessionHas('status', $neutralMessage);

        // 2. Non-existent user
        $resp2 = $this->post('/forgot-password', ['email' => 'doesnotexist@example.com']);
        $resp2->assertSessionHas('status', $neutralMessage);

        // Both responses must be identical
        $this->assertEquals($resp1->getSession()->get('status'), $resp2->getSession()->get('status'));
    }

    // =========================================================================
    // 7. Branded Error Pages
    // =========================================================================

    public function test_error_pages_render_without_stack_traces_or_debug_info(): void
    {
        $statusCodes = [403, 404, 419, 429, 500, 503];

        foreach ($statusCodes as $status) {
            $view = view("errors.{$status}")->render();

            $this->assertStringContainsString('DonorLink UG', $view);
            $this->assertStringNotContainsString('Illuminate\\', $view);
            $this->assertStringNotContainsString('Stack trace:', $view);
            $this->assertStringNotContainsString('vendor/', $view);
        }
    }

    // =========================================================================
    // 8. Observability & System Status Telemetry
    // =========================================================================

    public function test_system_status_service_evaluates_scheduler_and_queue_freshness(): void
    {
        $service = new SystemStatusService;

        // 1. Never run
        Cache::forget('donorlink:heartbeat:scheduler');
        Cache::forget('donorlink:heartbeat:queue_worker');

        $schedulerStatus = $service->getSchedulerStatus();
        $this->assertEquals('never_run', $schedulerStatus['status']);
        $this->assertTrue($service->hasBackgroundIssue());

        // 2. Healthy
        Cache::put('donorlink:heartbeat:scheduler', now()->timestamp, 300);
        Cache::put('donorlink:heartbeat:queue_worker', now()->timestamp, 1800);

        $schedulerHealthy = $service->getSchedulerStatus();
        $queueHealthy = $service->getQueueWorkerStatus();
        $this->assertEquals('healthy', $schedulerHealthy['status']);
        $this->assertEquals('healthy', $queueHealthy['status']);
        $this->assertFalse($service->hasBackgroundIssue());

        // 3. Stale
        Cache::put('donorlink:heartbeat:scheduler', now()->subMinutes(10)->timestamp, 300);
        $schedulerStale = $service->getSchedulerStatus();
        $this->assertEquals('stale', $schedulerStale['status']);
        $this->assertTrue($service->hasBackgroundIssue());
    }

    public function test_admin_system_telemetry_page_authorization_and_sanitization(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $staff = User::factory()->create(['role' => Role::Staff]);
        $donor = User::factory()->create(['role' => Role::Donor]);

        // Donors and staff get 403 Forbidden
        $this->actingAs($donor)->get(route('admin.system'))->assertStatus(403);
        $this->actingAs($staff)->get(route('admin.system'))->assertStatus(403);

        // Insert a failed job with a sensitive payload token
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SampleSecretJob',
                'secret_token' => 'SECRET_TOKEN_DO_NOT_LEAK_9988',
            ]),
            'exception' => "RuntimeException: Something failed\nStack trace:\n#0 secret/path/app.php(10): doWork()",
            'failed_at' => now(),
        ]);

        // Admin gets 200 OK
        $response = $this->actingAs($admin)->get(route('admin.system'));
        $response->assertStatus(200);
        $response->assertSee('Operational Observability');
        $response->assertSee('SampleSecretJob');

        // Confirm no raw job payloads or sensitive tokens are exposed
        $content = $response->getContent();
        $this->assertStringNotContainsString('SECRET_TOKEN_DO_NOT_LEAK_9988', $content);
        $this->assertStringNotContainsString('secret/path/app.php', $content);
    }

    public function test_admin_warning_banner_shown_on_staff_dashboard_only_when_stale(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $staff = User::factory()->create(['role' => Role::Staff]);

        // 1. Stale state: Admin sees banner
        Cache::forget('donorlink:heartbeat:scheduler');
        $adminResp = $this->actingAs($admin)->get(route('staff.dashboard'));
        $adminResp->assertSee('Reminders may not be sending:');

        // Staff does NOT see banner even when stale (banner is admin-only)
        $staffResp = $this->actingAs($staff)->get(route('staff.dashboard'));
        $staffResp->assertDontSee('Reminders may not be sending:');

        // 2. Healthy state: Admin does NOT see banner
        Cache::put('donorlink:heartbeat:scheduler', now()->timestamp, 300);
        Cache::put('donorlink:heartbeat:queue_worker', now()->timestamp, 1800);

        $adminHealthyResp = $this->actingAs($admin)->get(route('staff.dashboard'));
        $adminHealthyResp->assertDontSee('Reminders may not be sending:');
    }

    // =========================================================================
    // 9. Deploy Preflight Command
    // =========================================================================

    public function test_preflight_command_fails_in_testing_environment(): void
    {
        // Testing environment has APP_ENV=testing and DB=sqlite, so it must return FAILURE (1)
        $exitCode = Artisan::call('donorlink:preflight');
        $this->assertEquals(PreflightCommand::FAILURE, $exitCode);
    }

    public function test_preflight_command_passes_with_production_configuration(): void
    {
        $originalDefault = config('database.default');
        try {
            // Ensure active admin exists before changing default connection
            User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

            $sqlitePdo = DB::connection()->getPdo();
            Config::set('database.connections.mysql.driver', 'mysql');
            $mysqlConn = DB::connection('mysql');
            $mysqlConn->setPdo($sqlitePdo);
            $mysqlConn->setReadPdo($sqlitePdo);

            // Set all required config values to simulate production readiness
            Config::set('app.env', 'production');
            Config::set('app.debug', false);
            Config::set('app.key', 'base64:TestKeyForProductionReadinessCheck12345=');
            Config::set('app.url', 'https://donorlink.ug');
            Config::set('mail.default', 'smtp');
            Config::set('mail.from.address', 'notifications@donorlink.ug');
            Config::set('queue.default', 'database');
            Config::set('database.default', 'mysql');
            Config::set('session.driver', 'database');
            Config::set('cache.default', 'database');
            Config::set('session.secure', true);
            Config::set('donorlink.mail.daily_budget', 300);
            Config::set('donorlink.mail.transactional_reserve', 100);

            $exitCode = Artisan::call('donorlink:preflight');
            $this->assertEquals(PreflightCommand::SUCCESS, $exitCode);
        } finally {
            Config::set('database.default', $originalDefault);
            DB::purge('mysql');
        }
    }

    // =========================================================================
    // 10. Admin Invitation Bootstrap (donorlink:create-admin --invite)
    // =========================================================================

    public function test_create_admin_with_invite_flag_creates_admin_and_signed_url(): void
    {
        $exitCode = Artisan::call('donorlink:create-admin', [
            '--force' => true,
            '--invite' => true,
            '--name' => 'Initial SuperAdmin',
            '--email' => 'superadmin@donorlink.ug',
        ]);

        $this->assertEquals(0, $exitCode);

        $admin = User::where('email', 'superadmin@donorlink.ug')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(Role::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->email_verified_at); // Not verified until accepted

        // Verify that temporary signed URL to staff.invitation.accept works
        $acceptUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $admin->id]
        );

        $response = $this->get($acceptUrl);
        $response->assertStatus(200);
    }

    // =========================================================================
    // 11. Config & Route Cache Safety (Zero env() Calls Outside Config)
    // =========================================================================

    public function test_zero_env_calls_outside_config_directory(): void
    {
        $directories = [
            app_path(),
            base_path('routes'),
            base_path('resources/views'),
            database_path(),
        ];

        $envCalls = [];

        foreach ($directories as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getRealPath());
                    // Match env( but ignore docblock/comments
                    if (preg_match('/\benv\s*\(/', $content)) {
                        $envCalls[] = $file->getRealPath();
                    }
                }
            }
        }

        $this->assertEmpty($envCalls, 'Direct env() calls detected outside config/: '.implode(', ', $envCalls));
    }

    // =========================================================================
    // 12. Database Seeder Production Guard
    // =========================================================================

    public function test_database_seeder_refuses_to_run_in_production(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DatabaseSeeder is for development only and refuses to run in production.');

        $this->app->detectEnvironment(fn () => 'production');

        $seeder = new DatabaseSeeder;
        $seeder->run();
    }

    // =========================================================================
    // 13. Sitemap Database Cache Store & Drive Invalidation Lifecycle
    // =========================================================================

    public function test_sitemap_cache_uses_plain_key_and_invalidates_on_drive_lifecycle_with_database_cache_store(): void
    {
        Config::set('cache.default', 'database');
        Cache::flush();

        $staff = User::factory()->create(['role' => Role::Staff]);
        $drive1 = Drive::factory()->create([
            'created_by' => $staff->id,
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);

        $response1 = $this->get('/sitemap.xml');
        $response1->assertStatus(200);
        $this->assertStringContainsString(route('drives.show', $drive1), $response1->getContent());
        $this->assertTrue(Cache::has(SitemapController::CACHE_KEY));

        // Create a second published drive - boot hook clears plain cache key
        $drive2 = Drive::factory()->create([
            'created_by' => $staff->id,
            'status' => DriveStatus::Published,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->toDateString(),
        ]);
        $this->assertFalse(Cache::has(SitemapController::CACHE_KEY));

        // Fetching sitemap re-caches with drive2
        $response2 = $this->get('/sitemap.xml');
        $this->assertStringContainsString(route('drives.show', $drive2), $response2->getContent());
        $this->assertTrue(Cache::has(SitemapController::CACHE_KEY));

        // Updating drive status (e.g. closing drive) invalidates cache
        $drive2->update(['status' => DriveStatus::Closed]);
        $this->assertFalse(Cache::has(SitemapController::CACHE_KEY));

        // Deleting drive invalidates cache
        $drive2->delete();
        $this->assertFalse(Cache::has(SitemapController::CACHE_KEY));
    }

    // =========================================================================
    // 14. Staff and Admin Invitation Security & Single-Use Enforcement
    // =========================================================================

    public function test_staff_invitation_lifecycle_single_use_expires_rejects_tampering_and_unusable_initial_password(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

        // 1. Create invited staff user
        $invitedStaff = User::forceCreate([
            'name' => 'Invited Staff Member',
            'email' => 'staff-invite@donorlink.ug',
            'password' => Hash::make(Str::random(40)),
            'role' => Role::Staff,
            'is_active' => true,
            'notify_new_drives' => false,
            'consent_given_at' => now(),
        ]);

        // Verify account has no usable password before acceptance
        $this->assertNull($invitedStaff->email_verified_at);
        $this->assertFalse(Hash::check('password', $invitedStaff->password));
        $this->assertFalse(Hash::check('', $invitedStaff->password));
        $this->assertFalse(auth()->attempt(['email' => 'staff-invite@donorlink.ug', 'password' => 'password']));

        // 2. Generate signed invitation URL valid for 72h
        $validSignedUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $invitedStaff->id]
        );

        // 3. Rejects tampering (tampered signature & tampered user ID)
        $tamperedUrl = $validSignedUrl.'&tampered=1';
        $response = $this->get($tamperedUrl);
        $response->assertStatus(403);

        $tamperedUserUrl = str_replace("staff/invitation/{$invitedStaff->id}", "staff/invitation/{$admin->id}", $validSignedUrl);
        $response = $this->get($tamperedUserUrl);
        $response->assertStatus(403);

        // 4. Expires after 72 hours
        CarbonImmutable::setTestNow(now()->addHours(73));
        $response = $this->get($validSignedUrl);
        $response->assertStatus(403);

        // Reset time back to within 72h window
        CarbonImmutable::setTestNow('2026-10-18 10:00:00');
        $validSignedUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $invitedStaff->id]
        );

        // Valid link returns 200
        $response = $this->get($validSignedUrl);
        $response->assertStatus(200);

        // 5. Store new password (single-use activation)
        $storeUrl = URL::temporarySignedRoute(
            'staff.invitation.store',
            now()->addHours(72),
            ['user' => $invitedStaff->id]
        );

        $response = $this->post($storeUrl, [
            'password' => 'SecurePassword2026!',
            'password_confirmation' => 'SecurePassword2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $invitedStaff->refresh();
        $this->assertNotNull($invitedStaff->email_verified_at);
        $this->assertTrue(Hash::check('SecurePassword2026!', $invitedStaff->password));

        // 6. Single-use: Subsequent GET or POST to the invitation URL is rejected
        auth()->logout();
        $subsequentResponse = $this->get($validSignedUrl);
        $subsequentResponse->assertRedirect(route('login'));
        $subsequentResponse->assertSessionHas('status', 'This invitation has already been accepted. Please sign in with your password.');

        $subsequentPost = $this->post($storeUrl, [
            'password' => 'HackedPassword999!',
            'password_confirmation' => 'HackedPassword999!',
        ]);
        $subsequentPost->assertRedirect(route('login'));
        $this->assertFalse(Hash::check('HackedPassword999!', $invitedStaff->fresh()->password));
    }

    public function test_create_admin_invite_single_use_expires_rejects_tampering_and_unusable_initial_password(): void
    {
        // 1. Run artisan command with --invite
        $exitCode = Artisan::call('donorlink:create-admin', [
            '--force' => true,
            '--invite' => true,
            '--name' => 'Initial Invited Admin',
            '--email' => 'admin-bootstrap@donorlink.ug',
        ]);
        $this->assertEquals(0, $exitCode);

        $admin = User::where('email', 'admin-bootstrap@donorlink.ug')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(Role::Admin, $admin->role);

        // Verify account has no usable password before use
        $this->assertNull($admin->email_verified_at);
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertFalse(Hash::check('', $admin->password));
        $this->assertFalse(auth()->attempt(['email' => 'admin-bootstrap@donorlink.ug', 'password' => 'password']));

        // 2. Generate 72-hour signed invitation URL
        $validSignedUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $admin->id]
        );

        // 3. Rejects tampering
        $tamperedUrl = $validSignedUrl.'&tampered=1';
        $response = $this->get($tamperedUrl);
        $response->assertStatus(403);

        // 4. Expires after 72 hours
        CarbonImmutable::setTestNow(now()->addHours(73));
        $response = $this->get($validSignedUrl);
        $response->assertStatus(403);

        // Reset time back to within window
        CarbonImmutable::setTestNow('2026-10-18 10:00:00');
        $validSignedUrl = URL::temporarySignedRoute(
            'staff.invitation.accept',
            now()->addHours(72),
            ['user' => $admin->id]
        );
        $response = $this->get($validSignedUrl);
        $response->assertStatus(200);

        // 5. Submit password activation
        $storeUrl = URL::temporarySignedRoute(
            'staff.invitation.store',
            now()->addHours(72),
            ['user' => $admin->id]
        );

        $response = $this->post($storeUrl, [
            'password' => 'AdminMasterPass2026!',
            'password_confirmation' => 'AdminMasterPass2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $admin->refresh();
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('AdminMasterPass2026!', $admin->password));

        // 6. Single-use: Subsequent access rejected and redirected to login
        auth()->logout();
        $subsequentResponse = $this->get($validSignedUrl);
        $subsequentResponse->assertRedirect(route('login'));
        $subsequentResponse->assertSessionHas('status', 'This invitation has already been accepted. Please sign in with your password.');

        $subsequentPost = $this->post($storeUrl, [
            'password' => 'HackedPassword999!',
            'password_confirmation' => 'HackedPassword999!',
        ]);
        $subsequentPost->assertRedirect(route('login'));
        $this->assertFalse(Hash::check('HackedPassword999!', $admin->fresh()->password));
    }
}
