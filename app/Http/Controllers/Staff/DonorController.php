<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Enums\DonationOutcome;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DonationRecord;
use App\Models\DonorProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DonationRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonorController extends Controller
{
    /**
     * Allowed blood group values (standard ABO/Rh types).
     */
    public const VALID_BLOOD_GROUPS = [
        'A+', 'A-', 'A−',
        'B+', 'B-', 'B−',
        'AB+', 'AB-', 'AB−',
        'O+', 'O-', 'O−',
    ];

    /**
     * Searchable, paginated donor directory (listing is not audit-logged).
     */
    public function index(Request $request): View
    {
        Gate::authorize('access-staff');

        $search = trim((string) $request->input('search', ''));

        $donors = User::query()
            ->where('role', Role::Donor)
            ->with('donorProfile')
            ->when($search !== '', function ($query) use ($search) {
                $term = "%{$search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.staff.donors.index', [
            'donors' => $donors,
            'search' => $search,
        ]);
    }

    /**
     * Show the donor registration form for staff and administrators.
     */
    public function create(): View
    {
        Gate::authorize('access-staff');

        return view('pages.staff.donors.create', [
            'bloodGroups' => self::VALID_BLOOD_GROUPS,
        ]);
    }

    /**
     * Store a newly registered donor in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('access-staff');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', new \App\Rules\UgandanPhoneNumber],
            'date_of_birth' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'blood_group' => ['nullable', 'string', Rule::in(self::VALID_BLOOD_GROUPS)],
            'notes_for_staff' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'phone' => \App\Support\PhoneNumber::errorMessage(),
            'date_of_birth.after_or_equal' => 'Please enter a valid date of birth on or after 1900.',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
        ]);

        $plainPassword = ! empty($validated['password'])
            ? $validated['password']
            : \Illuminate\Support\Str::password(12);

        $donor = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $plainPassword) {
            $user = User::forceCreate([
                'name' => trim((string) $validated['name']),
                'email' => strtolower(trim((string) $validated['email'])),
                'phone' => \App\Support\PhoneNumber::normalise((string) $validated['phone']),
                'date_of_birth' => ! empty($validated['date_of_birth']) ? $validated['date_of_birth'] : null,
                'role' => Role::Donor,
                'is_active' => true,
                'consent_given_at' => now(),
                'email_verified_at' => now(),
                'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
            ]);

            $cleanBloodGroup = ! empty($validated['blood_group'])
                ? str_replace('−', '-', trim($validated['blood_group']))
                : null;

            $cleanNotes = ! empty($validated['notes_for_staff'])
                ? mb_substr(trim($validated['notes_for_staff']), 0, 500)
                : null;

            DonorProfile::create([
                'user_id' => $user->id,
                'blood_group' => $cleanBloodGroup,
                'last_donated_on' => null,
                'notes_for_staff' => $cleanNotes,
            ]);

            AuditLogger::record(auth()->user(), 'donor.registered', $user, [
                'donor_id' => $user->id,
                'registered_by_staff' => true,
            ]);

            return $user;
        });

        return redirect()->route('staff.donors.show', $donor)
            ->with('status', "Donor {$donor->name} registered successfully.");
    }

    /**
     * Donor profile, appointments, and donation records detail.
     * Viewing a donor detail record is security audit-logged.
     */
    public function show(User $user): View
    {
        Gate::authorize('access-staff');

        if (! $user->isDonor()) {
            abort(404);
        }

        $user->load([
            'donorProfile',
            'appointments' => fn ($q) => $q->with(['slot.drive', 'donationRecord'])->orderByDesc('created_at'),
            'donationRecords' => fn ($q) => $q->with(['drive', 'recordedBy'])->orderByDesc('recorded_at'),
        ]);

        // Security audit trail for accessing sensitive donor records
        AuditLogger::record(auth()->user(), 'donor.viewed', $user, [
            'donor_id' => $user->id,
        ]);

        return view('pages.staff.donors.show', [
            'donor' => $user,
            'profile' => $user->donorProfile,
        ]);
    }

    /**
     * Update administrative donor profile fields (blood group and staff notes).
     * Only changed field names are recorded in audit logs (never medical or note values).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('access-staff');

        if (! $user->isDonor()) {
            abort(404);
        }

        $validated = $request->validate([
            'blood_group' => ['nullable', 'string', Rule::in(self::VALID_BLOOD_GROUPS)],
            'notes_for_staff' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $user->donorProfile ?? DonorProfile::create(['user_id' => $user->id]);

        $cleanBloodGroup = ! empty($validated['blood_group'])
            ? str_replace('−', '-', trim($validated['blood_group']))
            : null;

        $cleanNotes = ! empty($validated['notes_for_staff'])
            ? mb_substr(trim($validated['notes_for_staff']), 0, 500)
            : null;

        $changedFields = [];

        if ($profile->blood_group !== $cleanBloodGroup) {
            $changedFields[] = 'blood_group';
            $profile->blood_group = $cleanBloodGroup;
        }

        if ($profile->notes_for_staff !== $cleanNotes) {
            $changedFields[] = 'notes_for_staff';
            $profile->notes_for_staff = $cleanNotes;
        }

        if (! empty($changedFields)) {
            $profile->save();

            AuditLogger::record(auth()->user(), 'donor.updated', $user, [
                'donor_id' => $user->id,
                'changed_fields' => $changedFields,
            ]);
        }

        return redirect()->route('staff.donors.show', $user)
            ->with('status', 'Donor profile details updated successfully.');
    }

    /**
     * Correct an existing donation record outcome and staff note.
     */
    public function correctDonation(
        Request $request,
        User $user,
        DonationRecord $record,
        DonationRecorder $recorder
    ): RedirectResponse {
        Gate::authorize('access-staff');
        if ($record->user_id !== $user->id) {
            abort(404);
        }

        $validated = $request->validate([
            'outcome' => ['required', 'in:donated,deferred'],
            'staff_note' => ['nullable', 'string', 'max:255'],
        ]);

        $recorder->correct(
            record: $record,
            newOutcome: DonationOutcome::from($validated['outcome']),
            staff: auth()->user(),
            note: $validated['staff_note'] ?? null
        );

        return redirect()->route('staff.donors.show', $user)
            ->with('status', 'Donation record outcome corrected successfully.');
    }
}
