<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the donor's password.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::default()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('donor.profile')->with('status', 'password-updated');
    }
}
