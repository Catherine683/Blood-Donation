<x-layouts.public
    :title="$drive->title . ' — Blood Donation Drive'"
    :meta-description="'View donation schedule, location details, and time slots for ' . $drive->title . ' at ' . $drive->venue . '.'"
>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-8" x-data="publicDriveBooking()">
        {{-- Breadcrumbs & Back Navigation --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <nav class="flex items-center text-xs font-medium text-neutral-500 gap-1.5" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-neutral-800 transition-colors">Home</a>
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('drives.index') }}" class="hover:text-neutral-800 transition-colors">Upcoming drives</a>
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-neutral-900 font-semibold truncate max-w-xs">{{ $drive->title }}</span>
            </nav>

            <a
                href="{{ route('drives.index') }}"
                class="inline-flex items-center text-xs font-bold text-neutral-600 hover:text-neutral-900 transition-colors self-start sm:self-auto"
            >
                &larr; Back to all blood drives
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('status'))
            <x-alert variant="success" :title="session('status')" />
        @endif
        @if (session('error'))
            <x-alert variant="danger" :title="session('error')" />
        @endif

        {{-- Banners for Special States --}}
        {{-- 1. Draft Banner (visible to staff & admin only) --}}
        @if ($drive->isDraft())
            <div class="rounded-2xl border border-warning-300 bg-warning-50 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-warning-900">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-warning-200 text-warning-900">
                        Draft Preview
                    </span>
                    <p class="text-xs sm:text-sm font-medium">
                        This drive is currently in draft mode and hidden from the public. You are viewing it as UBTS staff.
                    </p>
                </div>
                <a
                    href="{{ route('staff.drives.show', $drive) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-warning-300 text-warning-900 hover:bg-warning-100 transition-colors shrink-0"
                >
                    &larr; Return to Staff Portal
                </a>
            </div>
        @endif

        {{-- 2. Closed Banner --}}
        @if ($drive->isClosed())
            <div class="rounded-2xl border border-danger-200 bg-danger-50 p-5 flex items-center gap-3 text-danger-900" role="alert">
                <svg class="w-6 h-6 text-danger-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h2 class="text-sm font-bold text-danger-900">This blood donation drive is closed</h2>
                    <p class="text-xs text-danger-700 mt-0.5">
                        This donation drive has concluded and is no longer accepting online appointment reservations.
                    </p>
                </div>
            </div>
        @endif

        {{-- 3. Unverified Donor Alert --}}
        @auth
            @if (auth()->user()->isDonor() && !auth()->user()->hasVerifiedEmail())
                <div class="rounded-2xl border border-warning-200 bg-warning-50 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-warning-900">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-warning-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="text-sm font-bold text-warning-900">Email Verification Required</p>
                            <p class="text-xs text-warning-700">Please verify your email address to confirm and book a voluntary donation appointment.</p>
                        </div>
                    </div>
                    <a
                        href="{{ route('verification.notice') }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-white border border-warning-300 text-warning-900 hover:bg-warning-100 transition-colors shrink-0 shadow-2xs"
                    >
                        Verify Email &rarr;
                    </a>
                </div>
            @endif
        @endauth

        {{-- 4. Prominent Existing Booking Banner for this Drive --}}
        @if ($donorBooking)
            <div class="rounded-2xl border-2 border-primary-200 bg-gradient-to-r from-primary-50/80 via-white to-primary-50/40 p-6 space-y-4 shadow-xs relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-1 bg-primary-600"></div>

                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="space-y-1.5">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary-800 bg-primary-100/90 px-3 py-1 rounded-md">
                            <span class="w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
                            Your Confirmed Booking at This Drive
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-neutral-900 mt-1">
                            {{ \App\Support\DateHelper::formatDate($donorBooking->slot?->starts_at) }}
                        </h2>
                        <p class="text-sm font-bold text-primary-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ \App\Support\DateHelper::formatRange($donorBooking->slot?->starts_at, $donorBooking->slot?->ends_at) }} EAT
                        </p>
                        <p class="text-xs text-neutral-600">
                            Venue: <strong class="text-neutral-900">{{ $drive->venue }}</strong>@if($drive->address) &bull; {{ $drive->address }}@endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <button
                            type="button"
                            x-on:click="$dispatch('open-modal', 'cancel-booking-modal')"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-danger-700 bg-white border border-danger-200 hover:bg-danger-50 transition-colors min-h-[44px] shadow-2xs"
                        >
                            Cancel Booking
                        </button>
                    </div>
                </div>

                <div class="text-xs text-neutral-600 pt-3 border-t border-primary-200/60 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Need another slot? Select any available time below and click <strong>"Change to this time"</strong>.</span>
                </div>
            </div>
        @endif

        {{-- Main Drive Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs overflow-hidden">
            {{-- Crimson Top Accent Strip --}}
            <div class="h-1.5 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

            <div class="p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 text-xs font-bold text-primary-800 bg-primary-50 px-3 py-1 rounded-full border border-primary-100">
                            <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight font-sans">
                            {{ $drive->title }}
                        </h1>
                    </div>

                    {{-- Venue Directions Shortcut --}}
                    @php
                        $mapsQuery = urlencode($drive->venue . ($drive->address ? ', ' . $drive->address : ', Uganda'));
                    @endphp
                    <a
                        href="https://www.google.com/maps/search/?api=1&query={{ $mapsQuery }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-neutral-700 bg-neutral-100 hover:bg-neutral-200/80 transition-colors shrink-0 self-start shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Open Directions</span>
                        <svg class="w-3 h-3 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>

                {{-- Venue & Address info --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 rounded-xl bg-neutral-50 border border-neutral-100 text-sm">
                    <div>
                        <span class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-1">Collection Venue</span>
                        <p class="font-bold text-neutral-900 text-base">{{ $drive->venue }}</p>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-1">Location / Landmark</span>
                        <p class="text-neutral-700 font-medium">{{ $drive->address ?? 'Kampala, Uganda' }}</p>
                    </div>
                </div>

                {{-- Description --}}
                @if ($drive->description)
                    <div class="text-sm text-neutral-700 leading-relaxed border-t border-neutral-100 pt-4 space-y-2">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Information for Voluntary Donors</h2>
                        <p class="whitespace-pre-line text-neutral-600 leading-relaxed">{{ $drive->description }}</p>
                    </div>
                @endif

                {{-- Preparation Guidelines Bar --}}
                <div class="p-4 rounded-xl bg-primary-50/40 border border-primary-100 text-xs text-primary-900 space-y-2">
                    <div class="font-bold flex items-center gap-1.5 text-primary-800">
                        <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        What to bring &amp; donation checklist:
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[11px] text-neutral-600">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Valid Photo ID (NIN, Student ID, Passport)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Drink &ge; 500 mL water or fresh juice</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Eat a non-fatty meal 1-2 hours prior</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule & Slots Section --}}
        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-neutral-900 font-sans">
                        Available Donation Slots
                    </h2>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Select an appointment time to reserve your clinical session.
                    </p>
                </div>
                <div class="text-xs text-neutral-500 font-medium">
                    <span>Booking closes <strong>{{ $cutoffMinutes }} mins</strong> before slot start</span>
                </div>
            </div>

            @if ($groupedSlots->isEmpty())
                <div class="p-10 text-center bg-white rounded-2xl border border-neutral-200/90 shadow-xs space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-neutral-900">No Upcoming Slots Scheduled</h3>
                    <p class="text-xs text-neutral-500 max-w-sm mx-auto">
                        There are currently no active time slots for this drive. Please check back soon as UBTS clinical teams update daily session schedules.
                    </p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach ($groupedSlots as $dateStr => $daySlots)
                        @php
                            $firstSlot = $daySlots->first();
                            $dayLabel = \App\Support\DateHelper::formatDate($firstSlot->starts_at, 'l, d F Y');
                        @endphp
                        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 bg-neutral-50/80 border-b border-neutral-100 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-neutral-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $dayLabel }}
                                </h3>
                                <span class="text-xs font-semibold text-neutral-500">
                                    {{ $daySlots->count() }} {{ \Illuminate\Support\Str::plural('time slot', $daySlots->count()) }}
                                </span>
                            </div>

                            {{-- Slot Grid: Responsive Cards --}}
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($daySlots as $slot)
                                    @php
                                        $isFull = $slot->remaining === 0;
                                        $isInsideCutoff = $slot->starts_at->lt($cutoffThreshold);
                                        $isCurrentSlotBooking = $donorBooking && $donorBooking->slot_id === $slot->id;
                                    @endphp
                                    <div class="rounded-xl border p-4 flex flex-col justify-between space-y-3 transition-all duration-150 {{ $isCurrentSlotBooking ? 'border-primary-600 bg-primary-50/40 ring-2 ring-primary-600/20 shadow-xs' : ($isFull || $isInsideCutoff ? 'border-neutral-200 bg-neutral-50/60 opacity-80' : 'border-neutral-200/90 bg-white hover:border-neutral-300 hover:shadow-xs') }}">
                                        {{-- Header: Time and Availability Badge --}}
                                        <div>
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-bold text-neutral-900">
                                                    {{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}
                                                </span>
                                                <span class="text-[10px] font-mono font-semibold text-neutral-400">EAT</span>
                                            </div>

                                            <div class="mt-2 flex items-center gap-1.5 text-xs">
                                                @if ($isFull)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-danger-700 bg-danger-50 px-2 py-0.5 rounded">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-danger-500"></span>
                                                        Fully booked
                                                    </span>
                                                @elseif ($isInsideCutoff)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                                        Booking closed
                                                    </span>
                                                @elseif ($slot->remaining <= 3)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                        {{ $slot->remaining }} {{ Str::plural('spot', $slot->remaining) }} left
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        {{ $slot->remaining }} spots open
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Slot Action Button --}}
                                        <div class="pt-2 border-t border-neutral-100">
                                            @if ($drive->isClosed())
                                                <button type="button" disabled class="w-full text-center py-2 text-xs font-medium text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed">
                                                    Drive closed
                                                </button>
                                            @elseif ($isCurrentSlotBooking)
                                                <span class="w-full inline-flex items-center justify-center py-2 text-xs font-bold text-primary-800 bg-primary-100 rounded-lg">
                                                    Your Current Booking
                                                </span>
                                            @elseif ($isInsideCutoff)
                                                <button type="button" disabled class="w-full text-center py-2 text-xs font-medium text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed">
                                                    Slot Closed
                                                </button>
                                            @elseif ($isFull)
                                                <button type="button" disabled class="w-full text-center py-2 text-xs font-semibold text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed">
                                                    Full
                                                </button>
                                            @elseif (!auth()->check())
                                                <a
                                                    href="{{ route('login') }}?return_to={{ urlencode('/drives/' . $drive->id) }}"
                                                    class="w-full inline-flex items-center justify-center py-2 text-xs font-bold bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 transition-colors shadow-2xs"
                                                >
                                                    Log in to book
                                                </a>
                                            @elseif (auth()->user()->isDonor() && !auth()->user()->hasVerifiedEmail())
                                                <a
                                                    href="{{ route('verification.notice') }}"
                                                    class="w-full inline-flex items-center justify-center py-2 text-xs font-semibold text-warning-800 bg-warning-100 rounded-lg hover:bg-warning-200 transition-colors"
                                                >
                                                    Verify email
                                                </a>
                                            @elseif (!auth()->user()->isDonor())
                                                <span class="w-full inline-flex items-center justify-center py-2 text-xs text-neutral-400 italic">
                                                    Staff preview
                                                </span>
                                            @elseif ($donorBooking)
                                                <button
                                                    type="button"
                                                    x-on:click="openRescheduleModal({{ $slot->id }}, '{{ \App\Support\DateHelper::formatDate($slot->starts_at) }}', '{{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}')"
                                                    class="w-full inline-flex items-center justify-center py-2 text-xs font-bold text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors cursor-pointer"
                                                >
                                                    Change to this time
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    x-on:click="openBookModal({{ $slot->id }}, '{{ \App\Support\DateHelper::formatDate($slot->starts_at) }}', '{{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}')"
                                                    class="w-full inline-flex items-center justify-center py-2 text-xs font-bold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors shadow-xs cursor-pointer"
                                                >
                                                    Reserve Slot &rarr;
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ================= BOOKING MODALS ================= --}}

        {{-- 1. Book Slot Confirmation Modal --}}
        <x-modal name="book-slot-modal" title="Confirm Voluntary Donation Slot" maxWidth="md">
            <div class="space-y-4">
                <p class="text-sm text-neutral-600">
                    You are reserving a voluntary blood donation slot for:
                </p>

                <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-200 space-y-2 text-xs text-neutral-800">
                    <div>
                        <span class="text-neutral-500 font-medium">Drive:</span>
                        <strong class="text-neutral-900 ml-1">{{ $drive->title }}</strong>
                    </div>
                    <div>
                        <span class="text-neutral-500 font-medium">Venue:</span>
                        <span class="ml-1">{{ $drive->venue }}@if($drive->address) ({{ $drive->address }})@endif</span>
                    </div>
                    <div>
                        <span class="text-neutral-500 font-medium">Date &amp; Time:</span>
                        <strong class="text-primary-800 ml-1" x-text="selectedSlot.date + ' (' + selectedSlot.range + ' EAT)'"></strong>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-primary-50/50 border border-primary-100 text-[11px] text-primary-900 space-y-0.5">
                    <strong>Notice:</strong> Please arrive 10 minutes prior to your window. Bring a valid photo ID and hydrate well before the session.
                </div>

                <form method="POST" action="{{ route('donor.appointments.store') }}">
                    @csrf
                    <input type="hidden" name="slot_id" :value="selectedSlot.id">

                    <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                        <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'book-slot-modal')">
                            Cancel
                        </x-button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-bold bg-primary-600 text-white hover:bg-primary-700 transition-colors shadow-xs cursor-pointer min-h-[40px]"
                        >
                            Confirm Reservation
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>

        {{-- 2. Reschedule Slot Modal --}}
        @if ($donorBooking)
            <x-modal name="reschedule-slot-modal" title="Change Appointment Time" maxWidth="md">
                <div class="space-y-4">
                    <p class="text-sm text-neutral-600">
                        Confirm rescheduling your appointment on <strong class="text-neutral-900">{{ $drive->title }}</strong>:
                    </p>

                    <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-200 space-y-2 text-xs text-neutral-800">
                        <div>
                            <span class="text-neutral-500 font-medium">New Date & Time:</span>
                            <strong class="text-primary-800 ml-1" x-text="rescheduleSlot.date + ' (' + rescheduleSlot.range + ' EAT)'"></strong>
                        </div>
                        <div>
                            <span class="text-neutral-500 font-medium">Venue:</span>
                            <span class="ml-1">{{ $drive->venue }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-neutral-500">
                        Your previous time slot will be released for other voluntary donors immediately upon confirmation.
                    </p>

                    <form method="POST" :action="'/donor/appointments/{{ $donorBooking->id }}/reschedule'">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="new_slot_id" :value="rescheduleSlot.id">

                        <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'reschedule-slot-modal')">
                                Cancel
                            </x-button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-bold bg-primary-600 text-white hover:bg-primary-700 transition-colors shadow-xs cursor-pointer min-h-[40px]"
                            >
                                Confirm Change
                            </button>
                        </div>
                    </form>
                </div>
            </x-modal>

            {{-- 3. Cancel Booking Modal --}}
            <x-modal name="cancel-booking-modal" title="Cancel Voluntary Donation Booking" maxWidth="md">
                <div class="space-y-4">
                    <p class="text-sm text-neutral-600">
                        Are you sure you want to cancel your donation appointment for:
                    </p>

                    <div class="p-4 rounded-xl bg-danger-50/50 border border-danger-200 text-xs text-neutral-800 space-y-1">
                        <div>
                            <strong class="text-neutral-900 font-bold">{{ $drive->title }}</strong>
                        </div>
                        <div class="text-neutral-700">
                            {{ \App\Support\DateHelper::formatDate($donorBooking->slot?->starts_at) }} ({{ \App\Support\DateHelper::formatRange($donorBooking->slot?->starts_at, $donorBooking->slot?->ends_at) }} EAT)
                        </div>
                    </div>

                    <p class="text-xs text-neutral-500 leading-relaxed">
                        Your reserved slot will be returned to the UBTS public pool. You can book another session at any time.
                    </p>

                    <form method="POST" action="{{ route('donor.appointments.destroy', $donorBooking) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="appointment_id" value="{{ $donorBooking->id }}">

                        <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'cancel-booking-modal')">
                                Keep Appointment
                            </x-button>
                            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold bg-danger-600 text-white hover:bg-danger-700 transition-colors min-h-[40px] cursor-pointer">
                                Cancel Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endif
    </div>

    @push('scripts')
    <script>
        function publicDriveBooking() {
            return {
                selectedSlot: { id: null, date: '', range: '' },
                rescheduleSlot: { id: null, date: '', range: '' },

                openBookModal(id, date, range) {
                    this.selectedSlot = { id, date, range };
                    this.$dispatch('open-modal', 'book-slot-modal');
                },

                openRescheduleModal(id, date, range) {
                    this.rescheduleSlot = { id, date, range };
                    this.$dispatch('open-modal', 'reschedule-slot-modal');
                }
            }
        }
    </script>
    @endpush
</x-layouts.public>
