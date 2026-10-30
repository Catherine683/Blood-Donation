<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetOtpController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    private const MAX_OTP_ATTEMPTS = 5;

    public function create(Request $request): View
    {
        return view('pages.auth.verify-password-reset-otp', [
            'email' => strtolower(trim((string) $request->query('email', old('email', '')))),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $now = now();

            DB::table('password_reset_otps')->updateOrInsert(
                ['email' => $email],
                [
                    'code_hash' => Hash::make($code),
                    'attempts' => 0,
                    'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $user->notify(new PasswordResetOtp($code));
        }

        return redirect()->route('password.otp', ['email' => $email])
            ->with('status', 'If an account exists with this email address, we have sent a six-digit verification code.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $email = strtolower(trim($validated['email']));
        $code = $validated['code'];

        $token = DB::transaction(function () use ($email, $code): ?string {
            $otp = DB::table('password_reset_otps')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $otp || (int) $otp->attempts >= self::MAX_OTP_ATTEMPTS) {
                return null;
            }

            if (now()->greaterThanOrEqualTo($otp->expires_at)) {
                DB::table('password_reset_otps')->where('email', $email)->delete();

                return null;
            }

            if (! Hash::check($code, $otp->code_hash)) {
                DB::table('password_reset_otps')
                    ->where('email', $email)
                    ->increment('attempts');

                return null;
            }

            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                DB::table('password_reset_otps')->where('email', $email)->delete();

                return null;
            }

            DB::table('password_reset_otps')->where('email', $email)->delete();

            return Password::broker()->createToken($user);
        });

        if (! $token) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['code' => 'That code is invalid or expired. Request a new code and try again.']);
        }

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }
}
