<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Enums\DonationOutcome;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\User;
use App\Notifications\EmailChangeNotice;
use App\Notifications\EmailChangeVerification;
use App\Rules\UgandanPhoneNumber;
use App\Services\AuditLogger;
use App\Support\EmailMasker;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the donor profile page.
     */
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $donorProfile = $user->donorProfile;

        return view('pages.donor.profile', [
            'user' => $user,
            'donorProfile' => $donorProfile,
        ]);
    }

    /**
     * Update the donor's profile details (name, phone, DOB, notification preference).
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new UgandanPhoneNumber],
            'date_of_birth' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'notify_new_drives' => ['sometimes', 'boolean'],
        ]);

        $user->name = trim($validated['name']);
        $user->phone = PhoneNumber::normalise($validated['phone']);
        $user->date_of_birth = ! empty($validated['date_of_birth']) ? $validated['date_of_birth'] : null;

        if (array_key_exists('notify_new_drives', $validated)) {
            $user->notify_new_drives = (bool) $validated['notify_new_drives'];
        }

        $user->save();

        return redirect()->route('donor.profile')->with('status', 'profile-updated');
    }

    /**
     * Request a safe email change with current password confirmation.
     */
    public function requestEmailChange(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'new_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::notIn([$user->email]),
                Rule::unique('users', 'email'),
                Rule::unique('users', 'pending_email')->ignore($user->id),
            ],
            'current_password' => ['required', 'string'],
        ], [
            'new_email.not_in' => 'The new email address must be different from your current email address.',
            'new_email.unique' => 'This email address is already in use.',
        ]);

        if (! Hash::check($request->string('current_password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match our records.'],
            ]);
        }

        $newEmail = strtolower(trim($request->string('new_email')->value()));

        // Store as pending_email without clearing email verification or changing active email
        $user->pending_email = $newEmail;
        $user->save();

        // 1. Generate signed 24-hour verification link sent to the new address
        $verificationUrl = URL::temporarySignedRoute(
            'donor.email.verify-change',
            now()->addHours(24),
            ['user' => $user->id, 'email' => $newEmail]
        );

        $user->notify(new EmailChangeVerification($user, $newEmail, $verificationUrl));

        // 2. Send security notice to the current email address with masked new email
        $masked = EmailMasker::mask($newEmail);
        $user->notify(new EmailChangeNotice($user, $masked));

        return redirect()->route('donor.profile')
            ->with('status', "A verification link has been sent to {$newEmail}. Please confirm it within 24 hours.");
    }

    /**
     * Resend the pending email verification link.
     */
    public function resendEmailChange(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->pending_email) {
            return redirect()->route('donor.profile')->with('error', 'No pending email change request found.');
        }

        $verificationUrl = URL::temporarySignedRoute(
            'donor.email.verify-change',
            now()->addHours(24),
            ['user' => $user->id, 'email' => $user->pending_email]
        );

        $user->notify(new EmailChangeVerification($user, $user->pending_email, $verificationUrl));

        return redirect()->route('donor.profile')
            ->with('status', "Verification link resent to {$user->pending_email}.");
    }

    /**
     * Cancel a pending email change request.
     */
    public function cancelEmailChange(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->pending_email = null;
        $user->save();

        return redirect()->route('donor.profile')
            ->with('status', 'Pending email change request has been cancelled.');
    }

    /**
     * Verify and swap to the new email address.
     */
    public function confirmEmailChange(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'The verification link has expired or is invalid.');
        }

        $targetEmail = (string) $request->query('email');

        // Check if the link has been superseded or cancelled
        if (empty($user->pending_email) || $user->pending_email !== $targetEmail) {
            return redirect()->route('donor.profile')
                ->with('error', 'This email change request has expired, been superseded, or was cancelled.');
        }

        // Re-check uniqueness in case someone else claimed it in the interim
        $alreadyClaimed = User::query()
            ->where('email', $user->pending_email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyClaimed) {
            $user->pending_email = null;
            $user->save();

            return redirect()->route('donor.profile')
                ->with('error', 'This email address is now in use by another account. Please request a different email address.');
        }

        // Swap to the new email address and clear pending_email
        $oldEmail = $user->email;
        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->save();

        // Audit log (ids only)
        AuditLogger::record($user, 'donor.email_changed', $user, [
            'user_id' => $user->id,
        ]);

        return redirect()->route('donor.profile')
            ->with('status', "Your email address has been successfully updated to {$user->email}.");
    }

    /**
     * Download donor's own data in JSON format (GDPR / privacy compliance).
     */
    public function exportData(Request $request): JsonResponse
    {
        /** @var User $donor */
        $donor = $request->user();
        $profile = $donor->donorProfile;

        $appointments = $donor->appointments()
            ->with('slot.drive')
            ->orderByDesc('id')
            ->get()
            ->map(function (Appointment $appt) {
                $slot = $appt->slot;
                $drive = $slot?->drive;

                return [
                    'drive_title' => $drive?->title,
                    'venue' => $drive?->venue,
                    'slot_start_eat' => $slot ? Carbon::parse($slot->starts_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i') : null,
                    'slot_end_eat' => $slot ? Carbon::parse($slot->ends_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i') : null,
                    'status' => $appt->status->label(),
                ];
            })->toArray();

        $donationHistory = $donor->donationRecords()
            ->with('drive')
            ->orderByDesc('recorded_at')
            ->get()
            ->map(function (DonationRecord $record) {
                return [
                    'drive_title' => $record->drive?->title,
                    'venue' => $record->drive?->venue,
                    'date_eat' => Carbon::parse($record->recorded_at)->setTimezone('Africa/Kampala')->format('Y-m-d H:i:s'),
                    'outcome' => $record->outcome === DonationOutcome::Donated ? 'Donated' : 'Attended (no donation recorded)',
                ];
            })->toArray();

        $exportData = [
            'personal_details' => [
                'name' => $donor->name,
                'email' => $donor->email,
                'phone' => $donor->phone,
                'date_of_birth' => $donor->date_of_birth?->format('Y-m-d'),
                'consent_given_at' => $donor->consent_given_at?->setTimezone('Africa/Kampala')->format('Y-m-d H:i:s T'),
                'announcement_preference' => $donor->notify_new_drives ? 'opted_in' : 'opted_out',
            ],
            'blood_profile' => [
                'blood_group' => $profile?->blood_group,
                'last_donated_on' => $profile?->last_donated_on?->format('Y-m-d'),
            ],
            'appointments' => $appointments,
            'donation_history' => $donationHistory,
        ];

        // Audit log (ids only)
        AuditLogger::record($donor, 'donor.data_exported', $donor, [
            'user_id' => $donor->id,
        ]);

        $filename = 'donorlink-data-'.$donor->id.'-'.date('YmdHis').'.json';

        return response()->json($exportData, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
