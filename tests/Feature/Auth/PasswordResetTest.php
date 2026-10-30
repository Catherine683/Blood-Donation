<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_password_reset_code_can_be_requested_without_revealing_user_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect(route('password.otp', ['email' => $user->email]));
        $response->assertSessionHas('status', 'If an account exists with this email address, we have sent a six-digit verification code.');
        Notification::assertSentTo($user, PasswordResetOtp::class, function (PasswordResetOtp $notification) use ($user) {
            return preg_match('/^\d{6}$/', $notification->code) === 1
                && str_contains($notification->toMail($user)->render(), $notification->code);
        });
    }

    public function test_valid_code_redirects_to_the_password_set_page(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        $code = null;
        Notification::assertSentTo($user, PasswordResetOtp::class, function (PasswordResetOtp $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $response = $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ]);

        $response->assertRedirect();
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Reset password');
        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);

        $this->from(route('password.otp', ['email' => $user->email]))
            ->post(route('password.otp.verify'), [
                'email' => $user->email,
                'code' => $code,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_password_can_be_reset_after_valid_one_time_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        $code = null;
        Notification::assertSentTo($user, PasswordResetOtp::class, function (PasswordResetOtp $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $verifyResponse = $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'code' => $code,
        ]);

        $resetUrl = $verifyResponse->headers->get('Location');
        $token = basename((string) parse_url($resetUrl, PHP_URL_PATH));

        $this->get($resetUrl)->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_invalid_code_is_rejected_and_attempt_is_counted(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        $this->from(route('password.otp', ['email' => $user->email]))
            ->post(route('password.otp.verify'), [
                'email' => $user->email,
                'code' => '000000',
            ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('password_reset_otps', [
            'email' => $user->email,
            'attempts' => 1,
        ]);
    }

    public function test_expired_code_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        DB::table('password_reset_otps')->where('email', $user->email)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->from(route('password.otp', ['email' => $user->email]))
            ->post(route('password.otp.verify'), [
                'email' => $user->email,
                'code' => '123456',
            ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);
    }
}
