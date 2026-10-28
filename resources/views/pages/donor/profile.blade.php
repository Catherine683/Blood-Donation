<x-layouts.donor title="Profile & Credentials — Donor Portal">
    <div class="space-y-6 sm:space-y-8">
        {{-- System Header Banner --}}
        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-primary-50 text-primary-800 border border-primary-200/60">
                            <span class="w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
                            UBTS &bull; National Donor Registry Identity
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 font-mono">
                            #UBTS-D{{ str_pad((string)$user->id, 5, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Verified Account
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 font-sans">
                        Donor Profile &amp; Clinical Credentials
                    </h1>

                    <p class="text-sm text-neutral-500 max-w-2xl leading-relaxed">
                        Manage your voluntary donor registration records, update your verified contact details, and review your Uganda Blood Transfusion Services credentials.
                    </p>
                </div>

                {{-- Action Shortcuts --}}
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a
                        href="{{ route('donor.profile.export') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold bg-neutral-100 hover:bg-neutral-200 text-neutral-800 border border-neutral-200 transition-colors min-h-[44px]"
                    >
                        <svg class="w-4 h-4 mr-1.5 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export My Data (JSON)
                    </a>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('status'))
            <x-alert variant="success" title="Success">
                {{ session('status') === 'profile-updated' ? 'Your donor profile details have been saved successfully.' : session('status') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert variant="danger" title="Notice">
                {{ session('error') }}
            </x-alert>
        @endif

        {{-- Pending Email Change Alert --}}
        @if ($user->pending_email)
            <div class="rounded-2xl border border-warning-300 bg-warning-50/80 p-5 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-warning-200 text-warning-900">
                                Pending Email Verification
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-warning-900">
                            Change requested to <span class="underline font-bold">{{ $user->pending_email }}</span>
                        </p>
                        <p class="text-xs text-warning-700 leading-relaxed">
                            A 24-hour verification link has been emailed to that address. Your current address (<strong>{{ $user->email }}</strong>) remains active until verified.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('donor.profile.email-change.resend') }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm" class="min-h-[40px] bg-white">
                                Resend Link
                            </x-button>
                        </form>
                        <form method="POST" action="{{ route('donor.profile.email-change.cancel') }}">
                            @csrf
                            <x-button type="submit" variant="danger" size="sm" class="min-h-[40px]">
                                Cancel
                            </x-button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row items-start gap-8 w-full">
            {{-- Left Column: Profile Forms --}}
            <div class="flex-1 min-w-0 w-full space-y-8">
                {{-- Form 1: Contact Details --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-7 space-y-6">
                    <div class="border-b border-neutral-100 pb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-neutral-900">Personal &amp; Contact Details</h2>
                                <p class="text-xs text-neutral-500">Official information used by UBTS reception and phlebotomy units.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('donor.profile.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        {{-- Full Name --}}
                        <div>
                            <x-label for="name" value="Full Legal Name" required />
                            <x-input
                                id="name"
                                name="name"
                                type="text"
                                :value="old('name', $user->name)"
                                required
                                autocomplete="name"
                                :error="$errors->has('name')"
                                class="mt-1"
                            />
                            <x-error for="name" />
                        </div>

                        {{-- Current Email (Read-Only) --}}
                        <div>
                            <x-label for="current_email_display" value="Current Login Email" />
                            <div class="flex items-center gap-2 mt-1">
                                <input
                                    type="email"
                                    id="current_email_display"
                                    readonly
                                    disabled
                                    value="{{ $user->email }}"
                                    class="w-full text-sm rounded-xl border-neutral-200 bg-neutral-100 text-neutral-700 cursor-not-allowed py-2.5 px-3 min-h-[44px]"
                                />
                                <span class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Active
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] text-neutral-500">
                                To modify this address securely, complete the Email Update form below.
                            </p>
                        </div>

                        {{-- Phone Number --}}
                        <div>
                            <x-label for="phone" value="Ugandan Phone Number" required />
                            <x-input
                                id="phone"
                                name="phone"
                                type="tel"
                                inputmode="tel"
                                :value="old('phone', \App\Support\PhoneNumber::format($user->phone))"
                                required
                                autocomplete="tel"
                                :error="$errors->has('phone')"
                                class="mt-1"
                            />
                            <p class="mt-1 text-[11px] text-neutral-500">
                                Stored in international format (e.g., +256 772 123 456). Used for urgent SMS reminders and scheduling alerts.
                            </p>
                            <x-error for="phone" />
                        </div>

                        {{-- Date of Birth --}}
                        <div>
                            <x-label for="date_of_birth" value="Date of Birth (Optional)" />
                            <x-input
                                id="date_of_birth"
                                name="date_of_birth"
                                type="date"
                                :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))"
                                autocomplete="bday"
                                :error="$errors->has('date_of_birth')"
                                class="mt-1"
                            />
                            <p class="mt-1 text-[11px] text-neutral-500">
                                Voluntary whole blood donors must be between 17 and 65 years of age according to Uganda Ministry of Health guidelines.
                            </p>
                            <x-error for="date_of_birth" />
                        </div>

                        {{-- Email Preferences --}}
                        <div class="pt-3 border-t border-neutral-100">
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
                                <div class="space-y-0.5">
                                    <span class="text-xs font-bold text-neutral-900">
                                        Receive announcements for upcoming blood donation drives
                                    </span>
                                    <p class="text-[11px] text-neutral-500 leading-relaxed">
                                        We will notify you by email when new mobile blood drives and camps are organized near your region.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="pt-2">
                            <x-button type="submit" variant="primary" size="md" class="shadow-sm">
                                Save Profile Changes
                            </x-button>
                        </div>
                    </form>
                </div>

                {{-- Form 2: Safe Email Change --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-7 space-y-6">
                    <div class="border-b border-neutral-100 pb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-neutral-900">Update Registered Email</h2>
                                <p class="text-xs text-neutral-500">Requires your current password for security verification.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('donor.profile.email-change') }}" class="space-y-5">
                        @csrf

                        <div>
                            <x-label for="new_email" value="New Email Address" required />
                            <x-input
                                id="new_email"
                                name="new_email"
                                type="email"
                                inputmode="email"
                                required
                                autocomplete="email"
                                :value="old('new_email')"
                                :error="$errors->has('new_email')"
                                placeholder="new.email@example.com"
                                class="mt-1"
                            />
                            <x-error for="new_email" />
                        </div>

                        <div>
                            <x-label for="current_password_email" value="Current Account Password" required />
                            <x-input
                                id="current_password_email"
                                name="current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••••••"
                                :error="$errors->has('current_password')"
                                class="mt-1"
                            />
                            <x-error for="current_password" />
                        </div>

                        <div class="pt-2">
                            <x-button type="submit" variant="secondary" size="md" class="border-neutral-300">
                                Send Verification Link
                            </x-button>
                        </div>
                    </form>
                </div>

                {{-- Form 3: Update Password --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-7 space-y-6">
                    <div class="border-b border-neutral-100 pb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-neutral-900">Security Credentials</h2>
                                <p class="text-xs text-neutral-500">Protect your donor account with an updated, high-entropy password.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('donor.password.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-label for="current_password" value="Current Password" required />
                            <x-input
                                id="current_password"
                                name="current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••••••"
                                :error="$errors->has('current_password')"
                                class="mt-1"
                            />
                            <x-error for="current_password" />
                        </div>

                        <div>
                            <x-label for="password" value="New Password" required />
                            <x-input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••••••"
                                :error="$errors->has('password')"
                                class="mt-1"
                            />
                            <x-error for="password" />
                        </div>

                        <div>
                            <x-label for="password_confirmation" value="Confirm New Password" required />
                            <x-input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••••••"
                                :error="$errors->has('password_confirmation')"
                                class="mt-1"
                            />
                            <x-error for="password_confirmation" />
                        </div>

                        <div class="pt-2">
                            <x-button type="submit" variant="primary" size="md" class="shadow-sm">
                                Update Password
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Right Column: Credential Card & Regulatory --}}
            <div class="w-full lg:w-[380px] shrink-0 space-y-6">
                {{-- Digital UBTS Voluntary Donor Card --}}
                <div class="rounded-2xl bg-gradient-to-br from-neutral-900 via-neutral-950 to-primary-950 text-white p-6 shadow-md relative overflow-hidden border border-neutral-800">
                    {{-- Decorative Watermark --}}
                    <div class="absolute -right-6 -bottom-6 w-36 h-36 rounded-full bg-primary-600/15 blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 space-y-5">
                        <div class="flex items-center justify-between border-b border-white/10 pb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-primary-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                    UG
                                </div>
                                <div>
                                    <div class="text-[10px] tracking-wider uppercase text-neutral-400 font-bold">Republic of Uganda</div>
                                    <div class="text-xs font-bold text-white tracking-wide">UBTS Donor Registry</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                ACTIVE
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-neutral-400 block">Registered Donor Name</span>
                            <span class="text-base font-extrabold text-white tracking-tight block mt-0.5">{{ $user->name }}</span>
                            <span class="text-xs font-mono text-primary-300 mt-1 block">
                                #UBTS-D{{ str_pad((string)$user->id, 5, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-white/10 text-xs">
                            <div>
                                <span class="text-[10px] text-neutral-400 block uppercase font-medium">Blood Group</span>
                                <span class="text-lg font-black text-rose-400 block">
                                    {{ $donorProfile?->blood_group?->label() ?? 'Pending' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-neutral-400 block uppercase font-medium">Last Donation</span>
                                <span class="text-xs font-bold text-neutral-200 block mt-1">
                                    {{ $donorProfile?->last_donated_on ? \App\Support\DateHelper::formatDate($donorProfile->last_donated_on) : 'None on file' }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-white/10 flex items-center justify-between text-[10px] text-neutral-400 font-mono">
                            <span>REGIONAL BLOOD BANK</span>
                            <span>NAKASERO HQ</span>
                        </div>
                    </div>
                </div>

                {{-- Clinical Medical Record Card --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900">Clinical Data Integrity</h3>
                    </div>

                    <p class="text-xs text-neutral-600 leading-relaxed">
                        To maintain clinical safety, blood group classifications and recorded donation outcomes are entered and verified strictly by certified UBTS medical personnel during official blood drives.
                    </p>

                    <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-100 text-[11px] text-neutral-500 space-y-1">
                        <span class="font-bold text-neutral-700 block">Need to correct medical records?</span>
                        <span>Visit Nakasero Blood Bank HQ or present your official donor card at your next voluntary donation drive.</span>
                    </div>
                </div>

                {{-- Data Privacy & Regulatory Card --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900">Data Protection &amp; Privacy</h3>
                    </div>

                    <p class="text-xs text-neutral-600 leading-relaxed">
                        DonorLink UG is designed in accordance with the <em>Uganda Data Protection and Privacy Act 2019</em>. Voluntary donor information is protected under clinical confidentiality standards.
                    </p>

                    <div class="pt-2">
                        <a
                            href="{{ route('donor.profile.export') }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold bg-neutral-900 text-white hover:bg-neutral-800 transition-colors shadow-xs min-h-[44px]"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Full Data Package (JSON)
                        </a>
                    </div>
                </div>

                {{-- UBTS National Hotline Support --}}
                <div class="rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-xs space-y-3">
                    <div class="flex items-center gap-2 text-primary-800 font-bold text-xs">
                        <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        UBTS National Hotline
                    </div>
                    <div class="text-xs text-neutral-600 space-y-1">
                        <p class="font-bold text-neutral-900">Toll Free: 0800 100 066 / 0800 200 014</p>
                        <p class="text-[11px] text-neutral-500">Uganda Blood Transfusion Services, Nakasero Hill Road, P.O. Box 1772, Kampala, Uganda.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.donor>