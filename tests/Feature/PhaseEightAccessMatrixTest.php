<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\DriveStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhaseEightAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected User $unverifiedDonor;

    protected User $verifiedDonor;

    protected User $staff;

    protected User $admin;

    protected Drive $drive;

    protected Drive $draftDrive;

    protected Slot $slot;

    protected Appointment $appointment;

    protected DonationRecord $donationRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unverifiedDonor = User::factory()->create([
            'role' => Role::Donor,
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $this->verifiedDonor = User::factory()->create([
            'role' => Role::Donor,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->staff = User::factory()->create([
            'role' => Role::Staff,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->drive = Drive::forceCreate([
            'title' => 'Access Matrix Test Drive',
            'venue' => 'Main Test Venue',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
            'status' => DriveStatus::Published,
            'created_by' => $this->staff->id,
        ]);

        $this->draftDrive = Drive::forceCreate([
            'title' => 'Access Matrix Draft Drive',
            'venue' => 'Draft Venue',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
            'status' => DriveStatus::Draft,
            'created_by' => $this->staff->id,
        ]);

        $this->slot = Slot::forceCreate([
            'drive_id' => $this->drive->id,
            'starts_at' => now()->addHours(5),
            'ends_at' => now()->addHours(6),
            'capacity' => 10,
            'booked_count' => 0,
        ]);

        $this->appointment = Appointment::forceCreate([
            'user_id' => $this->verifiedDonor->id,
            'slot_id' => $this->slot->id,
            'status' => AppointmentStatus::Booked,
        ]);

        $this->donationRecord = DonationRecord::forceCreate([
            'user_id' => $this->verifiedDonor->id,
            'drive_id' => $this->drive->id,
            'appointment_id' => $this->appointment->id,
            'outcome' => DonationOutcome::Donated,
            'recorded_by' => $this->staff->id,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Complete Access Control Matrix mapping EVERY named route to expected behavior per actor.
     *
     * Result classes:
     * - '200' | 'ok': HTTP 200 or 204
     * - 'redirect_login': Redirects to login (302)
     * - 'redirect_verify': Redirects to email verification notice (302)
     * - 'redirect_dashboard': Redirects to dashboard / home (302)
     * - 'redirect': Redirects (302)
     * - '403': Forbidden (unauthorized role or invalid signature)
     * - 'action': Authorized to execute action (returns success or validation 422, but not 401/403)
     * - 'skip': Internal livewire / storage routes verified via dedicated unit tests
     */
    public function accessMatrix(): array
    {
        return [
            // Public Routes
            'home' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'privacy' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'robots' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'sitemap' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'drives.index' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'drives.show' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'well-known.passkeys' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],

            // Signed & Unsubscribe Routes (tested with valid signed URLs)
            'unsubscribe.announcements' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'unsubscribe.announcements.post' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'donor.email.verify-change' => [
                'guest' => 'redirect',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => 'redirect',
                'admin' => 'redirect',
            ],
            'staff.invitation.accept' => [
                'guest' => '200',
                'unverified' => '200',
                'donor' => '200',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.invitation.store' => [
                'guest' => 'redirect',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => 'redirect',
                'admin' => 'redirect',
            ],

            // Auth / Fortify Routes
            'login' => [
                'guest' => '200',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'login.store' => [
                'guest' => 'action',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'logout' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => 'redirect',
                'admin' => 'redirect',
            ],
            'register' => [
                'guest' => '200',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'register.store' => [
                'guest' => 'action',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'password.request' => [
                'guest' => '200',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'password.email' => [
                'guest' => 'action',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'password.reset' => [
                'guest' => '200',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'password.update' => [
                'guest' => 'action',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'verification.notice' => [
                'guest' => 'redirect_login',
                'unverified' => '200',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'verification.verify' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => 'redirect',
                'admin' => 'redirect',
            ],
            'verification.send' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => 'redirect',
                'admin' => 'redirect',
            ],

            // General Dashboard Redirect
            'dashboard' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],

            // Donor Portal Routes (/donor/*)
            'donor.home' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '200',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.appointments' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '200',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.appointments.store' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.appointments.destroy' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.appointments.reschedule' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.history' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '200',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.profile' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '200',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.profile.update' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.password.update' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.profile.email-change' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.profile.email-change.resend' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.profile.email-change.cancel' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'action',
                'staff' => '403',
                'admin' => '403',
            ],
            'donor.profile.export' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '200',
                'staff' => '403',
                'admin' => '403',
            ],

            // Staff Shell Routes (/staff/*)
            'staff.dashboard' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.export.period' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.create' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.store' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.show' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.edit' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.update' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.destroy' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.report' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.export.roster' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.export.summary' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.roster' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.drives.close-out' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.publish' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.close' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.reopen' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.announcement' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.slots.store' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.slots.preview' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.drives.slots.generate' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.slots.update' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.slots.destroy' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.donors' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.donors.show' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '200',
                'admin' => '200',
            ],
            'staff.donors.update' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'staff.donors.records.correct' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => 'action',
                'admin' => 'action',
            ],

            // Admin Only Routes (/admin/*)
            'admin.staff' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '403',
                'admin' => '200',
            ],
            'admin.audit-log' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '403',
                'admin' => '200',
            ],
            'admin.notifications.index' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '403',
                'admin' => '200',
            ],
            'admin.donations.export' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '403',
                'admin' => '200',
            ],
            'admin.system' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => '403',
                'staff' => '403',
                'admin' => '200',
            ],

            // User Settings (Staff & Admin only, donors redirect to donor.profile)
            'profile.edit' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => '200',
                'admin' => '200',
            ],
            'appearance.edit' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_verify',
                'donor' => 'redirect',
                'staff' => '200',
                'admin' => '200',
            ],
            'security.edit' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect',
                'donor' => 'redirect',
                'staff' => 'redirect',
                'admin' => 'redirect',
            ],

            // Passkeys & 2FA API endpoints
            'passkey.login-options' => [
                'guest' => 'action',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'passkey.login' => [
                'guest' => 'action',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'passkey.destroy' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.login' => [
                'guest' => 'redirect_login',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'two-factor.login.store' => [
                'guest' => 'redirect',
                'unverified' => 'redirect_dashboard',
                'donor' => 'redirect_dashboard',
                'staff' => 'redirect_dashboard',
                'admin' => 'redirect_dashboard',
            ],
            'two-factor.enable' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.confirm' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.disable' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.qr-code' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.secret-key' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.recovery-codes' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'two-factor.regenerate-recovery-codes' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'passkey.store' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'passkey.registration-options' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'passkey.confirm-options' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'passkey.confirm' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'password.confirm' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'password.confirm.store' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],
            'password.confirmation' => [
                'guest' => 'redirect_login',
                'unverified' => 'action',
                'donor' => 'action',
                'staff' => 'action',
                'admin' => 'action',
            ],

            // Internal Framework & Livewire endpoints
            '__flux.flag' => ['guest' => 'skip', 'unverified' => 'skip', 'donor' => 'skip', 'staff' => 'skip', 'admin' => 'skip'],
            'default-livewire.update' => ['guest' => 'skip', 'unverified' => 'skip', 'donor' => 'skip', 'staff' => 'skip', 'admin' => 'skip'],
            'livewire.preview-file' => ['guest' => 'skip', 'unverified' => 'skip', 'donor' => 'skip', 'staff' => 'skip', 'admin' => 'skip'],
            'livewire.upload-file' => ['guest' => 'skip', 'unverified' => 'skip', 'donor' => 'skip', 'staff' => 'skip', 'admin' => 'skip'],
            'storage.local' => ['guest' => 'skip', 'unverified' => 'skip', 'donor' => 'skip', 'staff' => 'skip', 'admin' => 'skip'],
            'storage.local.upload' => ['guest' => 'skip', 'unverified' => 'skip', 'donor' => 'skip', 'staff' => 'skip', 'admin' => 'skip'],
        ];
    }

    /**
     * Provide every named route as a test case.
     */
    public static function namedRouteProvider(): array
    {
        $matrix = (new self('dummy'))->accessMatrix();
        $cases = [];

        foreach (array_keys($matrix) as $routeName) {
            $cases[$routeName] = [$routeName];
        }

        return $cases;
    }

    /**
     * Strict check: Fails if ANY named route registered in routes/ has no entry in the matrix.
     */
    public function test_every_named_route_has_an_explicit_entry_in_the_access_matrix(): void
    {
        $allRoutes = Route::getRoutes()->getRoutesByName();
        $matrix = $this->accessMatrix();

        $missingEntries = [];
        foreach ($allRoutes as $name => $route) {
            if (! array_key_exists($name, $matrix)) {
                $missingEntries[] = $name;
            }
        }

        $this->assertEmpty(
            $missingEntries,
            'Security violation: The following routes exist in routes/ but have no explicit access matrix entry: '
            .implode(', ', $missingEntries)
        );
    }

    #[DataProvider('namedRouteProvider')]
    public function test_route_access_across_all_actors(string $routeName): void
    {
        $matrix = $this->accessMatrix();
        $this->assertArrayHasKey($routeName, $matrix, "Route [{$routeName}] is missing an access matrix entry.");

        $actors = ['guest', 'unverified', 'donor', 'staff', 'admin'];

        foreach ($actors as $actor) {
            $expected = $matrix[$routeName][$actor] ?? null;
            $this->assertNotNull($expected, "Route [{$routeName}] has no expected result defined for actor [{$actor}].");

            if ($expected === 'skip') {
                continue;
            }

            $user = match ($actor) {
                'guest' => null,
                'unverified' => $this->unverifiedDonor,
                'donor' => $this->verifiedDonor,
                'staff' => $this->staff,
                'admin' => $this->admin,
            };

            $response = $this->executeRouteRequest($routeName, $user);

            $this->assertExpectedMatrixResponse($response, $expected, $routeName, $actor);
        }
    }

    /**
     * Execute an HTTP request for a named route with proper method, parameters, and signatures.
     */
    protected function executeRouteRequest(string $routeName, ?User $user): TestResponse
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        RateLimiter::clear(Str::lower('forgot@example.com').'|127.0.0.1');
        RateLimiter::clear('127.0.0.1');

        if ($user !== null) {
            $this->actingAs($user);
        } else {
            auth()->logout();
        }

        /** @var RoutingRoute|null $route */
        $route = Route::getRoutes()->getByName($routeName);
        if (! $route) {
            $this->fail("Route [{$routeName}] not found in registered routes.");
        }

        $methods = $route->methods();
        $httpMethod = in_array('GET', $methods, true) ? 'GET' : $methods[0];
        $params = $this->resolveRouteParameters($route, $user);

        // Check if route requires signature
        $hasSignedMiddleware = collect($route->middleware())->contains(function ($m) {
            return is_string($m) && (str_contains($m, 'signed') || $m === 'signed');
        });

        if ($hasSignedMiddleware) {
            $url = URL::temporarySignedRoute($routeName, now()->addMinutes(30), $params);
        } else {
            $url = route($routeName, $params);
        }

        $payload = $this->dummyPayloadForRoute($routeName);

        return match ($httpMethod) {
            'GET', 'HEAD' => $this->get($url),
            'POST' => $this->post($url, $payload),
            'PUT' => $this->put($url, $payload),
            'DELETE' => $this->delete($url),
            default => $this->call($httpMethod, $url, $payload),
        };
    }

    /**
     * Resolve valid dummy route parameters.
     *
     * @return array<string, mixed>
     */
    protected function resolveRouteParameters(RoutingRoute $route, ?User $user): array
    {
        $params = [];
        $paramNames = $route->parameterNames();
        $routeName = $route->getName() ?? '';

        $isDraftOnlyRoute = in_array($routeName, ['staff.drives.destroy', 'staff.drives.publish'], true);
        $isDonorTargetRoute = in_array($routeName, ['staff.donors.show', 'staff.donors.update', 'staff.donors.records.correct', 'donor.email.verify-change'], true);
        $isInvitationRoute = in_array($routeName, ['staff.invitation.accept', 'staff.invitation.store'], true);

        foreach ($paramNames as $name) {
            $params[$name] = match ($name) {
                'drive' => $isDraftOnlyRoute ? $this->draftDrive->id : $this->drive->id,
                'slot' => $this->slot->id,
                'user' => $isInvitationRoute
                    ? $this->unverifiedDonor->id
                    : ($isDonorTargetRoute ? $this->verifiedDonor->id : ($user ? $user->id : $this->verifiedDonor->id)),
                'record' => $this->donationRecord->id,
                'appointment' => $this->appointment->id,
                'token' => 'dummy-token-sample',
                'id' => $user ? $user->id : $this->unverifiedDonor->id,
                'hash' => sha1($user ? $user->getEmailForVerification() : $this->unverifiedDonor->getEmailForVerification()),
                'path' => 'dummy',
                'passkey' => 1,
                'component' => 'dummy',
                'filename' => 'dummy.txt',
                default => 1,
            };
        }

        return $params;
    }

    /**
     * Provide dummy non-empty payload for state-changing routes to pass basic format checks.
     *
     * @return array<string, mixed>
     */
    protected function dummyPayloadForRoute(string $routeName): array
    {
        return match ($routeName) {
            'donor.appointments.store' => ['slot_id' => $this->slot->id],
            'donor.appointments.reschedule' => ['new_slot_id' => $this->slot->id],
            'staff.drives.store' => [
                'title' => 'Test Access Drive',
                'venue' => 'Test Venue',
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->addDay()->toDateString(),
            ],
            'staff.donors.records.correct' => [
                'outcome' => 'deferred',
                'explanation' => 'Matrix test correction explanation.',
            ],
            'login.store' => ['email' => 'test@example.com', 'password' => 'secret'],
            'register.store' => ['email' => 'reg@example.com', 'name' => 'Test Reg'],
            'password.email' => ['email' => 'forgot-'.uniqid().'@example.com'],
            default => [],
        };
    }

    /**
     * Assert response matches matrix outcome specification.
     */
    protected function assertExpectedMatrixResponse(
        TestResponse $response,
        string $expected,
        string $routeName,
        string $actor
    ): void {
        $statusCode = $response->getStatusCode();

        switch ($expected) {
            case '200':
            case 'ok':
                $this->assertTrue(
                    in_array($statusCode, [200, 204], true),
                    "Expected 200 for [{$routeName}] as [{$actor}], received {$statusCode}."
                );
                break;

            case 'redirect_login':
                $this->assertSame(302, $statusCode);
                $this->assertTrue(
                    str_contains($response->headers->get('Location') ?? '', '/login'),
                    "Expected redirect to login for [{$routeName}] as [{$actor}], got: ".$response->headers->get('Location')
                );
                break;

            case 'redirect_verify':
                $this->assertSame(302, $statusCode);
                $this->assertTrue(
                    str_contains($response->headers->get('Location') ?? '', '/email/verify'),
                    "Expected redirect to email verification for [{$routeName}] as [{$actor}], got: ".$response->headers->get('Location')
                );
                break;

            case 'redirect_dashboard':
            case 'redirect':
                $this->assertSame(302, $statusCode);
                break;

            case '403':
                $this->assertSame(403, $statusCode);
                break;

            case 'action':
                // Action allowed past authorization/middleware (e.g. 200, 302 redirect, or 422 validation error)
                $this->assertNotEquals(
                    401,
                    $statusCode,
                    "Expected action authorization for [{$routeName}] as [{$actor}], received 401 Unauthorized."
                );
                $this->assertNotEquals(
                    403,
                    $statusCode,
                    "Expected action authorization for [{$routeName}] as [{$actor}], received 403 Forbidden."
                );
                break;

            default:
                if (is_numeric($expected)) {
                    $this->assertSame((int) $expected, $statusCode);
                }
                break;
        }
    }
}
