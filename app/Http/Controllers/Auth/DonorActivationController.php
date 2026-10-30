<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DonorActivationController extends Controller
{
    public function show(Request $request, User $user): View|RedirectResponse
    {
        if (! $request->hasValidSignature() || ! $user->isDonor()) {
            abort(403, 'The activation link is invalid.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->with('status', 'This donor account has already been activated. Please sign in.');
        }

        return view('pages.auth.accept-donor-activation', ['user' => $user]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature() || ! $user->isDonor()) {
            abort(403, 'The activation link is invalid.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->with('status', 'This donor account has already been activated. Please sign in.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Your donor account is active. Welcome to DonorLink UG.');
    }
}
