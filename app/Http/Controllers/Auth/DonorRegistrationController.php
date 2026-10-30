<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\DonorRegistrationOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DonorRegistrationController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    private const MAX_OTP_ATTEMPTS = 5;

    public function store(Request $request, CreateNewUser $createNewUser): RedirectResponse
    {
        $user = $createNewUser->create($request->only([
            'name',
            'email',
            'phone',
            'date_of_birth',
            'password',
            'password_confirmation',
            'consent',
        ]));

        $this->sendCode($user);

        return redirect()->route('register.otp', ['email' => $user->email])
            ->with('status', 'We sent a six-digit verification code to your email. The code expires in 10 minutes.');
    }

    public function create(Request $request): View
    {
        return view('pages.auth.verify-donor-registration-otp', [
            'email' => strtolower(trim((string) $request->query('email', old('email', '')))),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = DB::transaction(function () use ($email, $validated): ?User {
            $otp = DB::table('donor_registration_otps')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $otp || (int) $otp->attempts >= self::MAX_OTP_ATTEMPTS) {
                return null;
            }

            if (now()->greaterThanOrEqualTo($otp->expires_at)) {
                DB::table('donor_registration_otps')->where('email', $email)->delete();

                return null;
            }

            if (! Hash::check($validated['code'], $otp->code_hash)) {
                DB::table('donor_registration_otps')
                    ->where('email', $email)
                    ->increment('attempts');

                return null;
            }

            $user = User::query()
                ->where('email', $email)
                ->where('role', Role::Donor)
                ->lockForUpdate()
                ->first();

            DB::table('donor_registration_otps')->where('email', $email)->delete();

            if (! $user) {
                return null;
            }

            if (! $user->hasVerifiedEmail()) {
                $user->forceFill([
                    'email_verified_at' => now(),
                    'is_active' => true,
                ])->save();
            }

            return $user;
        });

        if (! $user) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['code' => 'That code is invalid or expired. Request a new code and try again.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Your email is verified. Welcome to DonorLink UG.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()
            ->where('email', $email)
            ->where('role', Role::Donor)
            ->whereNull('email_verified_at')
            ->whereIn('email', DB::table('donor_registration_otps')->select('email'))
            ->first();

        if ($user) {
            $this->sendCode($user);
        }

        return redirect()->route('register.otp', ['email' => $email])
            ->with('status', 'If an unverified donor account exists for this email, a new verification code has been sent.');
    }

    private function sendCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = now();

        DB::table('donor_registration_otps')->updateOrInsert(
            ['email' => $user->email],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $user->notify(new DonorRegistrationOtp($code));
    }
}
