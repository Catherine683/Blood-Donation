<x-layouts.donor title="Donor Dashboard — DonorLink UG">
    <div class="space-y-6 sm:space-y-8">
        {{-- System Header Banner --}}
        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-8 relative overflow-hidden">
            {{-- Crimson Top Accent Strip --}}
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-primary-50 text-primary-800 border border-primary-200/60">
                            <span class="w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
                            UBTS &bull; Voluntary Donor Registry
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 font-mono">
                            #UBTS-D{{ str_pad((string)$user->id, 5, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Verified Donor Account
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 font-sans">
                        Welcome back, {{ $user->name }}
                    </h1>

                    <p class="text-sm text-neutral-500 max-w-2xl leading-relaxed">
                        Uganda Blood Transfusion Services (UBTS) scheduling & voluntary attendance record system.
                    </p>
                </div>

                {{-- Action Shortcuts --}}
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <x-button :href="route('drives.index')" variant="primary" size="md" class="shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Book Donation Slot
                    </x-button>

                    <x-button :href="route('donor.appointments')" variant="secondary" size="md" class="bg-white border-neutral-300">
                        <svg class="w-4 h-4 mr-1.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Appointments ({{ $activeAppointmentsCount }})
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('status'))
            <x-alert variant="success" :title="session('status')" />
        @endif
        @if (session('error'))
            <x-alert variant="danger" :title="session('error')" />
        @endif

        {{-- Active Appointment Highlight Banner (if booked) --}}
        @if ($nextAppointment)
            @php
                $nextSlot = $nextAppointment->slot;
                $nextDrive = $nextSlot?->drive;
            @endphp
            <div class="rounded-2xl border border-primary-200/80 bg-gradient-to-r from-primary-50/70 via-white to-primary-50/40 p-5 sm:p-6 shadow-xs relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-primary-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-primary-800">
                                    Upcoming Scheduled Donation
                                </span>
                                <x-badge :variant="$nextAppointment->status->badgeVariant()" size="sm">
                                    {{ $nextAppointment->status->label() }}
                                </x-badge>
                            </div>
                            <h2 class="text-lg font-bold text-neutral-900 leading-snug">
                                {{ $nextDrive?->title ?? 'UBTS Blood Donation Session' }}
                            </h2>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-600 font-medium">
                                <span class="flex items-center gap-1 text-primary-800 font-semibold">
                                    <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ \App\Support\DateHelper::formatDate($nextSlot?->starts_at) }} &bull; {{ \App\Support\DateHelper::formatRange($nextSlot?->starts_at, $nextSlot?->ends_at) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    {{ $nextDrive?->venue }}@if($nextDrive?->address) &bull; {{ $nextDrive?->address }}@endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-start md:self-center shrink-0">
                        <a
                            href="{{ route('donor.appointments') }}"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold bg-white text-neutral-800 border border-neutral-300 hover:bg-neutral-50 transition-colors shadow-xs"
                        >
                            Manage Booking &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- 4 Clinical & Impact Metric Cards Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            {{-- Card 1: Next Appointment Status --}}
            <div class="bg-white rounded-xl border border-neutral-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-neutral-500 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Next Session</span>
                        <div class="w-8 h-8 rounded-lg bg-neutral-100 text-neutral-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    @if ($nextAppointment)
                        <div class="text-lg font-bold text-neutral-900 leading-snug truncate">
                            {{ \App\Support\DateHelper::formatDate($nextAppointment->slot?->starts_at) }}
                        </div>
                        <p class="text-xs text-neutral-500 mt-1 truncate">
                            {{ \App\Support\DateHelper::formatRange($nextAppointment->slot?->starts_at, $nextAppointment->slot?->ends_at) }}
                        </p>
                    @else
                        <div class="text-base font-semibold text-neutral-700">None Scheduled</div>
                        <p class="text-xs text-neutral-400 mt-1">Ready for your next donation?</p>
                    @endif
                </div>
                <div class="pt-4 mt-4 border-t border-neutral-100">
                    <a href="{{ $nextAppointment ? route('donor.appointments') : route('drives.index') }}" class="text-xs font-semibold text-primary-700 hover:text-primary-900 inline-flex items-center gap-1">
                        {{ $nextAppointment ? 'View session details' : 'Browse blood drives' }} &rarr;
                    </a>
                </div>
            </div>

            {{-- Card 2: Lifetime Donations --}}
            <div class="bg-white rounded-xl border border-neutral-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-neutral-500 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Units Donated</span>
                        <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-neutral-900 tracking-tight">
                        {{ $donationsCount }}
                    </div>
                    <p class="text-xs text-neutral-500 mt-1">Verified on-site by UBTS staff</p>
                </div>
                <div class="pt-4 mt-4 border-t border-neutral-100">
                    <a href="{{ route('donor.history') }}" class="text-xs font-semibold text-primary-700 hover:text-primary-900 inline-flex items-center gap-1">
                        View donation records &rarr;
                    </a>
                </div>
            </div>

            {{-- Card 3: Estimated Lives Impacted --}}
            <div class="bg-white rounded-xl border border-neutral-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-neutral-500 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Lives Impacted</span>
                        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-extrabold text-primary-700 tracking-tight">
                        {{ $livesImpacted }}
                    </div>
                    <p class="text-xs text-neutral-500 mt-1">1 unit can save up to 3 patients</p>
                </div>
                <div class="pt-4 mt-4 border-t border-neutral-100 text-xs text-neutral-400">
                    Whole blood &bull; Platelets &bull; Plasma
                </div>
            </div>

            {{-- Card 4: Last Donation --}}
            <div class="bg-white rounded-xl border border-neutral-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-neutral-500 mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Last Donated</span>
                        <div class="w-8 h-8 rounded-lg bg-neutral-100 text-neutral-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-lg font-bold text-neutral-900 leading-snug">
                        {{ $donorProfile?->last_donated_on ? $donorProfile->last_donated_on->format('d M Y') : 'First Donation Pending' }}
                    </div>
                    <p class="text-xs text-neutral-500 mt-1">
                        {{ $donorProfile?->last_donated_on ? $donorProfile->last_donated_on->diffForHumans() : 'Join an upcoming camp' }}
                    </p>
                </div>
                <div class="pt-4 mt-4 border-t border-neutral-100">
                    <span class="text-xs font-medium text-emerald-700 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Voluntary Donor Status
                    </span>
                </div>
            </div>
        </div>

        {{-- Main 2-Column Clinical Dashboard Layout --}}
        <div class="flex flex-col lg:flex-row items-start gap-8 w-full">
            
            {{-- Left Column: Drives & History --}}
            <div class="flex-1 min-w-0 w-full space-y-8">
                
                {{-- Section 1: Upcoming Blood Donation Drives --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-neutral-200/80 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-neutral-900">Official Scheduled Blood Drives</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Upcoming collection drives conducted by UBTS medical teams across Uganda</p>
                        </div>
                        <a href="{{ route('drives.index') }}" class="text-xs font-semibold text-primary-700 hover:text-primary-900 transition-colors shrink-0">
                            View all drives &rarr;
                        </a>
                    </div>

                    @if ($upcomingDrives->isEmpty())
                        <div class="p-8 text-center">
                            <p class="text-sm font-medium text-neutral-700">No scheduled drives right now</p>
                            <p class="text-xs text-neutral-500 mt-1">Please check back soon for upcoming donation opportunities.</p>
                        </div>
                    @else
                        <div class="divide-y divide-neutral-100">
                            @foreach ($upcomingDrives as $drive)
                                @php
                                    $hasUpcomingSlots = ($drive->upcoming_slots_count ?? 0) > 0;
                                    $spotsLeft = max(0, (int) ($drive->upcoming_capacity ?? 0) - (int) ($drive->upcoming_booked ?? 0));
                                @endphp
                                <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-neutral-50/60 transition-colors">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-primary-800 bg-primary-50 px-2.5 py-0.5 rounded border border-primary-200/50">
                                                {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
                                            </span>
                                            @if (!$hasUpcomingSlots)
                                                <span class="text-[11px] font-medium text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded">No open slots</span>
                                            @elseif ($spotsLeft === 0)
                                                <span class="text-[11px] font-semibold text-danger-700 bg-danger-50 px-2 py-0.5 rounded">Fully booked</span>
                                            @else
                                                <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/60">
                                                    {{ $spotsLeft }} {{ Str::plural('slot', $spotsLeft) }} open
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-sm sm:text-base font-bold text-neutral-900 leading-snug">
                                            <a href="{{ route('drives.show', $drive) }}" class="hover:text-primary-700 transition-colors">
                                                {{ $drive->title }}
                                            </a>
                                        </h3>

                                        <p class="text-xs text-neutral-500 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            <span>{{ $drive->venue }}@if($drive->address) &bull; {{ $drive->address }}@endif</span>
                                        </p>
                                    </div>

                                    <div class="shrink-0 self-start sm:self-center">
                                        <x-button :href="route('drives.show', $drive)" variant="secondary" size="sm" class="bg-white hover:bg-neutral-50 shadow-xs border-neutral-300 font-semibold">
                                            Book Appointment &rarr;
                                        </x-button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Section 2: Verified Clinical Donation Records --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-neutral-200/80 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-neutral-900">Verified Clinical Donation Log</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Historical records confirmed on-site by certified UBTS medical phlebotomists</p>
                        </div>
                        <a href="{{ route('donor.history') }}" class="text-xs font-semibold text-primary-700 hover:text-primary-900 transition-colors shrink-0">
                            Full history &rarr;
                        </a>
                    </div>

                    @if ($recentDonations->isEmpty())
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-neutral-800">No donation history recorded yet</p>
                            <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                                When you attend a scheduled blood drive and complete a donation, the UBTS clinical team will record your session outcome here.
                            </p>
                            <div class="mt-4">
                                <x-button :href="route('drives.index')" variant="secondary" size="sm">
                                    Find a Drive to Donate
                                </x-button>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-neutral-50 text-[11px] font-bold uppercase tracking-wider text-neutral-500 border-b border-neutral-200">
                                        <th class="py-3 px-5">Date</th>
                                        <th class="py-3 px-5">Venue / Drive</th>
                                        <th class="py-3 px-5">Attendant</th>
                                        <th class="py-3 px-5 text-right">Outcome</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-100 text-xs">
                                    @foreach ($recentDonations as $record)
                                        <tr class="hover:bg-neutral-50/50 transition-colors">
                                            <td class="py-3.5 px-5 font-semibold text-neutral-900">
                                                {{ $record->recorded_at->format('d M Y') }}
                                            </td>
                                            <td class="py-3.5 px-5 text-neutral-700">
                                                {{ $record->drive?->title ?? 'UBTS Collection Centre' }}
                                                @if($record->drive?->venue)
                                                    <span class="block text-[11px] text-neutral-400">{{ $record->drive->venue }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-5 text-neutral-600">
                                                {{ $record->recordedBy?->name ?? 'UBTS Clinical Staff' }}
                                            </td>
                                            <td class="py-3.5 px-5 text-right">
                                                <x-badge :variant="$record->outcome->badgeVariant()" size="sm">
                                                    {{ $record->outcome->label() }}
                                                </x-badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Right Column: Official Card & Clinical Checklist --}}
            <div class="w-full lg:w-[380px] shrink-0 space-y-6">
                
                {{-- UBTS Official Digital Donor Credential Card --}}
                <div class="rounded-2xl border border-primary-200/80 shadow-sm p-6 relative overflow-hidden bg-gradient-to-br from-neutral-900 via-neutral-900 to-neutral-950 text-white">
                    {{-- Decorative Watermark Emblem --}}
                    <div class="absolute -right-6 -bottom-6 w-36 h-36 opacity-10 pointer-events-none">
                        <x-logo size="lg" />
                    </div>

                    <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary-500 animate-pulse"></span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-300">UBTS Donor Card</span>
                        </div>
                        <span class="text-[11px] font-mono text-neutral-400">UG-MOH</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <span class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Donor Full Name</span>
                            <h3 class="text-base font-bold text-white tracking-wide truncate">
                                {{ $user->name }}
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-1">
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Donor ID</span>
                                <p class="text-xs font-mono font-bold text-primary-300">
                                    UBTS-D{{ str_pad((string)$user->id, 5, '0', STR_PAD_LEFT) }}
                                </p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Blood Group</span>
                                <p class="text-xs font-bold text-white">
                                    {{ $donorProfile?->blood_group ?? 'Pending Typing' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-1 border-t border-white/10">
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Registered</span>
                                <p class="text-xs font-medium text-neutral-300">
                                    {{ $user->created_at?->format('d M Y') }}
                                </p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Status</span>
                                <p class="text-xs font-semibold text-emerald-400">Active Voluntary</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                        <a href="{{ route('donor.profile') }}" class="text-xs font-semibold text-primary-400 hover:text-primary-300 transition-colors inline-flex items-center gap-1">
                            Manage donor record &rarr;
                        </a>
                        <span class="text-[10px] text-neutral-500 uppercase tracking-widest font-mono">Official</span>
                    </div>
                </div>

                {{-- Clinical Preparation & Readiness Checklist --}}
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900">Donation Day Protocol</h3>
                            <p class="text-[11px] text-neutral-500">Official UBTS clinical readiness guidelines</p>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2 text-xs text-neutral-600">
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-neutral-100 text-neutral-700 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">1</span>
                            <div>
                                <strong class="text-neutral-800">Hydrate generously:</strong>
                                <span>Drink 3 to 4 glasses of water or juice within 3 hours prior to your scheduled time.</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-neutral-100 text-neutral-700 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">2</span>
                            <div>
                                <strong class="text-neutral-800">Eat a balanced meal:</strong>
                                <span>Do not donate on an empty stomach. Enjoy an iron-rich meal prior to donating.</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-neutral-100 text-neutral-700 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">3</span>
                            <div>
                                <strong class="text-neutral-800">Carry your identification:</strong>
                                <span>Present your National ID (NIN), passport, or institutional card upon arrival.</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-neutral-100 text-neutral-700 font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">4</span>
                            <div>
                                <strong class="text-neutral-800">Strict on-site screening:</strong>
                                <span>Qualified phlebotomists verify temperature, Hb levels, and blood pressure on site.</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact & Support Info Card --}}
                <div class="bg-neutral-50 rounded-2xl border border-neutral-200/80 p-5 space-y-2.5 text-xs">
                    <h4 class="font-bold text-neutral-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Uganda Blood Transfusion Services
                    </h4>
                    <p class="text-neutral-500 leading-relaxed">
                        Nakasero Blood Bank, Plot 1 Nakasero Hill Road, Kampala.<br>
                        Toll-free Hotline: <strong>0800 100 066</strong>
                    </p>
                </div>

            </div>

        </div>
    </div>
</x-layouts.donor>
