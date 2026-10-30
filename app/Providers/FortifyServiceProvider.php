<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Models\User;
use App\Support\RedirectHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);

        // Neutral password reset responses that do not reveal user existence
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponse::class, function () {
            return new class implements SuccessfulPasswordResetLinkRequestResponse
            {
                public function toResponse($request)
                {
                    return back()->with('status', 'If an account exists with this email address, we have sent a six-digit verification code.');
                }
            };
        });

        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, function () {
            return new class implements FailedPasswordResetLinkRequestResponse
            {
                public function toResponse($request)
                {
                    return back()->with('status', 'If an account exists with this email address, we have sent a six-digit verification code.');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureAuthentication();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            if ($request->has('return_to')) {
                $returnTo = (string) $request->query('return_to');
                $safePath = RedirectHelper::sanitizeRelativePath($returnTo);
                if ($safePath !== null) {
                    session()->put('return_to', $safePath);
                    session()->put('url.intended', $safePath);
                }
            }

            return view('pages.auth.login');
        });
        Fortify::verifyEmailView(fn () => view('pages.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages.auth.confirm-password'));
        Fortify::registerView(fn () => view('pages.auth.register'));
        Fortify::resetPasswordView(fn () => view('pages.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages.auth.forgot-password'));
    }

    /**
     * Configure custom authentication logic (inactive user check).
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', (string) $request->input(Fortify::username()))->first();

            if ($user && Hash::check((string) $request->input('password'), $user->password)) {
                if (! $user->is_active) {
                    $message = $user->hasVerifiedEmail()
                        ? __('This account is inactive. Please contact UBTS staff.')
                        : __('This account is not activated. Follow the verification instructions sent to your email.');

                    throw ValidationException::withMessages([
                        Fortify::username() => [$message],
                    ]);
                }

                return $user;
            }

            return null;
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });

        // Registration rate limit: at least 30 registrations per hour per IP (set to 30/hour).
        // Keying by IP prevents automated bulk account creation abuse while accommodating on-site mobile drive
        // registration desks where multiple voluntary donors register from a shared mobile hotspot or clinic Wi-Fi.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(30)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(30)->by($request->ip());
        });

        RateLimiter::for('registration-otp-verify', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(10)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('registration-otp-resend', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinutes(5, 3)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        // Forgot-password rate limit: keyed on email + IP combination (3 attempts per minute).
        // Keying by email+IP prevents distributed denial-of-service/lockout against a single user's inbox from multiple IPs,
        // while stopping an individual IP from spraying password reset requests across arbitrary recipient emails.
        RateLimiter::for('forgot-password', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));
            $throttleKey = Str::transliterate($email.'|'.$request->ip());

            return [
                Limit::perMinute(3)->by($throttleKey),
                Limit::perMinutes(5, 3)->by(Str::lower($email)),
            ];
        });

        RateLimiter::for('password-otp-verify', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(10)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('verification-resend', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('invitation-resend', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('email-change-requests', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Unsubscribe rate limit: very high limit (300 requests per minute per IP).
        // The unsubscribe endpoint is already cryptographically guarded by HMAC-SHA256 temporary signed URLs.
        // A high ceiling prevents corporate security link scanners or corporate proxy IP pooling from blocking
        // legitimate donors from opting out of drive marketing announcements.
        RateLimiter::for('unsubscribe', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('donor-data-download', function (Request $request) {
            return Limit::perHour(3)->by($request->user()?->id ?: $request->ip());
        });
    }
}
