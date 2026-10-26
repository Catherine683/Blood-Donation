<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Show password creation form for invited staff/admin member.
     */
    public function show(Request $request, User $user): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'The invitation link has expired or is invalid.');
        }

        if ($user->email_verified_at !== null) {
            return redirect()->route('login')
                ->with('status', 'This invitation has already been accepted. Please sign in with your password.');
        }

        return view('pages.auth.accept-invitation', [
            'user' => $user,
        ]);
    }

    /**
     * Store the chosen password and activate the invited account.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'The invitation link has expired or is invalid.');
        }

        if ($user->email_verified_at !== null) {
            return redirect()->route('login')
                ->with('status', 'This invitation has already been accepted. Please sign in with your password.');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($request->string('password')->value());
        $user->email_verified_at = now();
        $user->is_active = true;
        $user->save();

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('status', 'Your password has been configured successfully. Welcome to DonorLink UG.');
    }
}
