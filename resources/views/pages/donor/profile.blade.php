<x-layouts.donor title="Profile — Donor Portal">
    <x-page-header
        title="Donor Profile"
        description="Manage your personal details, review your recorded donation history, and update security credentials."
    />

    <div class="max-w-3xl space-y-8">
        {{-- Status Notification --}}
        @if (session('status'))
            <x-alert variant="success" title="Success">
                {{ session('status') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert variant="danger" title="Error">
                {{ session('error') }}
            </x-alert>
        @endif

        {{-- Pending Email Change Alert --}}
        @if ($user->pending_email)
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 sm:p-5 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-warning-200 text-warning-900">
                                Pending Email Change
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-warning-900">
                            Change requested to <span class="underline font-bold">{{ $user->pending_email }}</span>
                        </p>
                        <p class="text-xs text-warning-700 leading-relaxed">
                            A 24-hour verification link has been sent to that address. Your current address (<strong>{{ $user->email }}</strong>) remains active for logins and reminders until confirmed.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('donor.profile.email-change.resend') }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm" class="min-h-[44px]">
                                Resend Link
                            </x-button>
                        </form>
                        <form method="POST" action="{{ route('donor.profile.email-change.cancel') }}">
                            @csrf
                            <x-button type="submit" variant="danger" size="sm" class="min-h-[44px]">
                                Cancel
                            </x-button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Section 1: Your Details --}}
        <x-card title="Your Details" description="Keep your contact details up to date so UBTS can reach you regarding appointment updates." padding="lg">
            <form method="POST" action="{{ route('donor.profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Full Name --}}
                <div>
                    <x-label for="name" value="Full name" required />
                    <x-input
                        id="name"
                        name="name"
                        type="text"
                        :value="old('name', $user->name)"
                        required
                        autocomplete="name"
                        :error="$errors->has('name')"
                    />
                    <x-error for="name" />
                </div>

                {{-- Current Email (Read-only reference) --}}
                <div>
                    <x-label for="current_email_display" value="Email address" />
                    <div class="flex items-center gap-2 mt-1">
                        <input
                            type="email"
                            id="current_email_display"
                            readonly
                            disabled
                            value="{{ $user->email }}"
                            class="w-full text-sm rounded-lg border-neutral-300 bg-neutral-100 text-neutral-700 cursor-not-allowed py-2.5 px-3 min-h-[44px]"
                        />
                        <x-badge variant="success" size="md">Verified</x-badge>
                    </div>
                    <p class="mt-1.5 text-xs text-neutral-500">
                        To change your email address securely without losing access, use the form in the section below.
                    </p>
                </div>

                {{-- Phone Number --}}
                <div>
                    <x-label for="phone" value="Phone number" required />
                    <x-input
                        id="phone"
                        name="phone"
                        type="tel"
                        inputmode="tel"
                        :value="old('phone', \App\Support\PhoneNumber::format($user->phone))"
                        required
                        autocomplete="tel"
                        :error="$errors->has('phone')"
                    />
                    <p class="mt-1.5 text-xs text-neutral-500">
                        Ugandan phone number (e.g. 0772 123 456 or +256 772 123 456). Stored in international E.164 format.
                    </p>
                    <x-error for="phone" />
                </div>

                {{-- Date of Birth (Optional) --}}
                <div>
                    <x-label for="date_of_birth" value="Date of birth (optional)" />
                    <x-input
                        id="date_of_birth"
                        name="date_of_birth"
                        type="date"
                        :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))"
                        autocomplete="bday"
                        :error="$errors->has('date_of_birth')"
                    />
                    <p class="mt-1.5 text-xs text-neutral-500">
                        Used solely for donor verification on site during reception.
                    </p>
                    <x-error for="date_of_birth" />
                </div>

                {{-- Email Notification Preferences --}}
                <div class="pt-4 border-t border-neutral-200/80 space-y-3">
                    <h3 class="text-sm font-semibold text-neutral-900">Email Preferences</h3>

                    <input type="hidden" name="notify_new_drives" value="0">
                    <label for="notify_new_drives" class="flex items-start gap-3 cursor-pointer">
                        <input
                            id="notify_new_drives"
                            name="notify_new_drives"
                            type="checkbox"
                            value="1"
                            {{ old('notify_new_drives', $user->notify_new_drives) ? 'checked' : '' }}
                            class="mt-1 w-4 h-4 rounded-md border-neutral-300 text-primary-600 focus:ring-primary-600"
                        />
                        <div class="space-y-1">
                            <span class="text-sm font-medium text-neutral-800">
                                Email me when a new drive is announced
                            </span>
                            <p class="text-xs text-neutral-500 leading-normal">
                                Reminders (24 hours and 2 hours before your appointment) and booking confirmations are essential parts of the service and are always sent.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="pt-2">
                    <x-button type="submit" variant="primary" size="md" class="min-h-[44px]">
                        Save changes
                    </x-button>
                </div>
            </form>
        </x-card>

        {{-- Section: Safe Email Change --}}
        <x-card title="Change Email Address" description="Update the email address associated with your account. You will receive a verification link at the new address, and your current email remains active until confirmed." padding="lg">
            <form method="POST" action="{{ route('donor.profile.email-change') }}" class="space-y-4">
                @csrf

                <div>
                    <x-label for="new_email" value="New email address" required />
                    <x-input
                        id="new_email"
                        name="new_email"
                        type="email"
                        inputmode="email"
                        required
                        autocomplete="email"
                        :value="old('new_email')"
                        :error="$errors->has('new_email')"
                        placeholder="you@example.com"
                    />
                    <x-error for="new_email" />
                </div>

                <div>
                    <x-label for="current_password_email" value="Current account password" required />
                    <x-input
                        id="current_password_email"
                        name="current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                        :error="$errors->has('current_password')"
                    />
                    <x-error for="current_password" />
                </div>

                <div class="pt-2">
                    <x-button type="submit" variant="primary" size="md" class="min-h-[44px]">
                        Request Email Change
                    </x-button>
                </div>
            </form>
        </x-card>

        {{-- Section 2: Your Donation Information (Staff-Entered Read-Only) --}}
        <x-card title="Your Donation Information" description="Medical and donation records verified in person by UBTS personnel." padding="lg">
            @if ($donorProfile && ($donorProfile->blood_group || $donorProfile->last_donated_on))
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200/70">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Blood Group</dt>
                        <dd class="mt-1 text-2xl font-bold text-primary-800">
                            {{ $donorProfile->blood_group?->label() ?? 'Not set' }}
                        </dd>
                        <p class="mt-1 text-xs text-neutral-500">Recorded by UBTS staff</p>
                    </div>

                    <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200/70">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Last Recorded Donation</dt>
                        <dd class="mt-1 text-2xl font-bold text-neutral-900">
                            {{ $donorProfile->last_donated_on ? \App\Support\DateHelper::formatDate($donorProfile->last_donated_on) : 'None recorded' }}
                        </dd>
                        <p class="mt-1 text-xs text-neutral-500">Derived from completed sessions</p>
                    </div>
                </dl>
            @else
                <div class="rounded-xl bg-neutral-50 border border-neutral-200/80 p-5 text-sm text-neutral-600">
                    <p class="font-medium text-neutral-900 mb-1">No donation records on file yet</p>
                    <p class="text-xs text-neutral-500">
                        Your blood group and donation history will appear here once verified and entered by UBTS healthcare personnel during a donation drive.
                    </p>
                </div>
            @endif
        </x-card>

        {{-- Section: Data Export (Download My Data) --}}
        <x-card title="Your Data & Privacy" description="Download a copy of your personal details, appointment bookings, and donation records in machine-readable JSON format." padding="lg">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-neutral-900">
                        Download My Personal Data
                    </p>
                    <p class="text-xs text-neutral-500 leading-relaxed max-w-lg">
                        Includes your contact information, consent timestamp, appointment records, and donation outcomes. Staff clinical notes are excluded.
                    </p>
                </div>
                <a
                    href="{{ route('donor.profile.export') }}"
                    class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold rounded-lg bg-neutral-900 text-white hover:bg-neutral-800 transition-colors shadow-xs shrink-0 min-h-[44px]"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download My Data (JSON)
                </a>
            </div>
        </x-card>

        {{-- Section 3: Change Password --}}
        <x-card title="Security Credentials" description="Ensure your account is protected with a strong, unique password." padding="lg">
            <form method="POST" action="{{ route('donor.password.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Current Password --}}
                <div>
                    <x-label for="current_password" value="Current password" required />
                    <x-input
                        id="current_password"
                        name="current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                        :error="$errors->has('current_password')"
                    />
                    <x-error for="current_password" />
                </div>

                {{-- New Password --}}
                <div>
                    <x-label for="password" value="New password" required />
                    <x-input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••••••"
                        :error="$errors->has('password')"
                    />
                    <x-error for="password" />
                </div>

                {{-- Confirm New Password --}}
                <div>
                    <x-label for="new_password_confirmation" value="Confirm new password" required />
                    <x-input
                        id="new_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••••••"
                        :error="$errors->has('password_confirmation')"
                    />
                    <x-error for="password_confirmation" />
                </div>

                <div class="pt-2">
                    <x-button type="submit" variant="primary" size="md" class="min-h-[44px]">
                        Update password
                    </x-button>
                </div>
            </form>
        </x-card>

        {{-- Section 4: Account Removal Note --}}
        <x-card title="Account Removal" description="Information regarding account deletion and personal data erasure." padding="lg">
            <div class="rounded-xl bg-neutral-50 border border-neutral-200/80 p-5">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-neutral-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-neutral-700 space-y-2 leading-relaxed">
                        <p class="font-medium text-neutral-900">
                            Self-service account deletion is not available.
                        </p>
                        @if (config('donorlink.privacy.removal_process'))
                            <p>
                                {{ config('donorlink.privacy.removal_process') }}
                            </p>
                        @endif
                        <p class="text-xs text-neutral-500">
                            Because blood collection and voluntary donation records form an integral part of national health tracking and clinical safety, donor records cannot be deleted automatically.
                        </p>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</x-layouts.donor>
