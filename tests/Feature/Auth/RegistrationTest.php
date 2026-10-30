<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\DonorRegistrationOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '0772 123 456',
            'password' => 'password',
            'password_confirmation' => 'password',
            'consent' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('register.otp', ['email' => 'test@example.com']));

        $this->assertGuest();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isDonor());
        $this->assertFalse($user->is_active);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, DonorRegistrationOtp::class);

        $this->get('/donor')->assertRedirect(route('login'));
    }

    public function test_valid_registration_code_verifies_and_signs_in_the_donor(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Jane Donor',
            'email' => 'jane@example.com',
            'phone' => '0772123456',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'consent' => '1',
        ])->assertRedirect(route('register.otp', ['email' => 'jane@example.com']));

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $code = null;
        Notification::assertSentTo($user, DonorRegistrationOtp::class, function (DonorRegistrationOtp $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $this->post(route('register.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertTrue($user->fresh()->is_active);
        $this->assertDatabaseMissing('donor_registration_otps', ['email' => $user->email]);
    }

    public function test_invalid_registration_code_increments_attempt_count(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Jane Donor',
            'email' => 'jane@example.com',
            'phone' => '0772123456',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'consent' => '1',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $wrongCode = null;
        Notification::assertSentTo($user, DonorRegistrationOtp::class, function (DonorRegistrationOtp $notification) use (&$wrongCode): bool {
            $wrongCode = $notification->code === '000000' ? '000001' : '000000';

            return true;
        });

        $this->post(route('register.otp.verify'), [
            'email' => 'jane@example.com',
            'code' => $wrongCode,
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseHas('donor_registration_otps', [
            'email' => 'jane@example.com',
            'attempts' => 1,
        ]);
        $this->assertGuest();
    }

    public function test_admin_created_donor_cannot_use_self_registration_otp_resend(): void
    {
        Notification::fake();

        User::factory()->unverified()->create([
            'email' => 'admin-created@example.com',
            'role' => Role::Donor,
            'is_active' => false,
        ]);

        $this->post(route('register.otp.resend'), [
            'email' => 'admin-created@example.com',
        ])->assertRedirect(route('register.otp', ['email' => 'admin-created@example.com']));

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('donor_registration_otps', ['email' => 'admin-created@example.com']);
    }
}
