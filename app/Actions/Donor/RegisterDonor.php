<?php

declare(strict_types=1);

namespace App\Actions\Donor;

use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\User;
use App\Rules\UgandanPhoneNumber;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterDonor
{
    /**
     * Validate and register a new voluntary blood donor.
     *
     * @param  array<string, mixed>  $input
     */
    public function register(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', new UgandanPhoneNumber],
            'date_of_birth' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'password' => ['required', 'string', 'confirmed', Password::default()],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'You must agree to DonorLink UG storing your details and confirm you have read the privacy notice.',
            'phone' => PhoneNumber::errorMessage(),
            'date_of_birth.after_or_equal' => 'Please enter a valid date of birth on or after 1900.',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::forceCreate([
                'name' => trim((string) $input['name']),
                'email' => strtolower(trim((string) $input['email'])),
                'phone' => PhoneNumber::normalise((string) $input['phone']),
                'date_of_birth' => ! empty($input['date_of_birth']) ? $input['date_of_birth'] : null,
                'role' => Role::Donor,
                'is_active' => true,
                'consent_given_at' => now(),
                'password' => Hash::make((string) $input['password']),
            ]);

            DonorProfile::create([
                'user_id' => $user->id,
                'blood_group' => null,
                'last_donated_on' => null,
                'notes_for_staff' => null,
            ]);

            return $user;
        });
    }
}
