<x-layouts.public
    :title="$drive->title . ' — Blood Donation Drive'"
    :meta-description="'View donation schedule, location details, and time slots for ' . $drive->title . ' at ' . $drive->venue . '.'"
>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-8" x-data="publicDriveBooking()">
        {{-- Breadcrumbs --}}
        <nav class="flex items-center text-xs font-medium text-neutral-500 gap-1.5" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-neutral-800 transition-colors">Home</a>
            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('drives.index') }}" class="hover:text-neutral-800 transition-colors">Upcoming drives</a>
            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-neutral-800 truncate max-w-xs">{{ $drive->title }}</span>
        </nav>

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
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-warning-900">
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
            <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 flex items-center gap-3 text-danger-900" role="alert">
                <svg class="w-5 h-5 text-danger-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h2 class="text-sm font-bold text-danger-900">This drive is closed</h2>
                    <p class="text-xs text-danger-700 mt-0.5">
                        This blood donation drive is closed and no longer accepting appointment bookings.
                    </p>
                </div>
            </div>
        @endif

        {{-- 3. Unverified Donor Alert --}}
        @auth
            @if (auth()->user()->isDonor() && !auth()->user()->hasVerifiedEmail())
                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-warning-900">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-warning-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-xs sm:text-sm font-medium">
                            Please verify your email address to book a donation slot.
                        </p>
                    </div>
                    <a
                        href="{{ route('verification.notice') }}"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-warning-300 text-warning-900 hover:bg-warning-100 transition-colors shrink-0"
                    >
                        Verify Email &rarr;
                    </a>
                </div>
            @endif
        @endauth

        {{-- 4. Prominent Existing Booking Banner for this Drive --}}
        @if ($donorBooking)
            <div class="rounded-2xl border-2 border-primary-200 bg-primary-50/60 p-5 sm:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary-800 bg-primary-100/80 px-2.5 py-0.5 rounded-md">
                            <span class="w-2 h-2 rounded-full bg-primary-600"></span>
                            Your Confirmed Booking at This Drive
                        </span>
                        <h2 class="text-lg sm:text-xl font-bold text-neutral-900 mt-1">
                            {{ \App\Support\DateHelper::formatDate($donorBooking->slot?->starts_at) }}
                        </h2>
                        <p class="text-sm font-semibold text-primary-900">
                            {{ \App\Support\DateHelper::formatRange($donorBooking->slot?->starts_at, $donorBooking->slot?->ends_at) }}
                        </p>
                        <p class="text-xs text-neutral-600 mt-1">
                            Venue: <strong>{{ $drive->venue }}</strong>@if($drive->address) &bull; {{ $drive->address }}@endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <button
                            type="button"
                            x-on:click="$dispatch('open-modal', 'cancel-booking-modal')"
                            class="inline-flex items-center justify-center px-3.5 py-2 rounded-lg text-xs font-semibold text-danger-700 bg-white border border-danger-200 hover:bg-danger-50 transition-colors min-h-[44px]"
                        >
                            Cancel Appointment
                        </button>
                    </div>
                </div>

                <div class="text-xs text-neutral-600 pt-3 border-t border-primary-200/60 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>To reschedule, select any available slot below and click <strong>"Change to this time"</strong>.</span>
                </div>
            </div>
        @endif

        {{-- Main Drive Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200/80 shadow-xs overflow-hidden">
            <div class="p-6 sm:p-8 space-y-6">
                <div>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-primary-700 bg-primary-50 px-3 py-1 rounded-full mb-3">
                        <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight">
                        {{ $drive->title }}
                    </h1>
                </div>

                {{-- Venue & Address info --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-neutral-50 border border-neutral-100 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-1">Venue</span>
                        <p class="font-bold text-neutral-900">{{ $drive->venue }}</p>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-1">Address / Landmark</span>
                        <p class="text-neutral-700">{{ $drive->address ?? 'Kampala, Uganda' }}</p>
                    </div>
                </div>

                {{-- Description --}}
                @if ($drive->description)
                    <div class="text-sm text-neutral-700 leading-relaxed border-t border-neutral-100 pt-4 space-y-2">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Information for Donors</h2>
                        <p class="whitespace-pre-line">{{ $drive->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Schedule & Slots Section --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-neutral-900">
                        Available Donation Slots
                    </h2>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Select a time slot to reserve your donation appointment.
                    </p>
                </div>
            </div>

            @if ($groupedSlots->isEmpty())
                <div class="p-8 text-center bg-white rounded-xl border border-neutral-200 text-neutral-500 text-sm">
                    <p class="font-medium text-neutral-700">No upcoming slots currently scheduled</p>
                    <p class="text-xs text-neutral-500 mt-1">Please check back soon as UBTS staff update daily session capacities.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach ($groupedSlots as $dateStr => $daySlots)
                        @php
                            $firstSlot = $daySlots->first();
                            $dayLabel = \App\Support\DateHelper::formatDate($firstSlot->starts_at, 'l, d F Y');
                        @endphp
                        <div class="bg-white rounded-xl border border-neutral-200 shadow-xs overflow-hidden">
                            <div class="px-5 py-3 bg-neutral-50 border-b border-neutral-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $dayLabel }}
                                </h3>
                                <span class="text-xs text-neutral-500">
                                    {{ $daySlots->count() }} sessions
                                </span>
                            </div>

                            <div class="divide-y divide-neutral-100">
                                @foreach ($daySlots as $slot)
                                    @php
                                        $isFull = $slot->remaining === 0;
                                        $isInsideCutoff = $slot->starts_at->lt($cutoffThreshold);
                                        $isCurrentSlotBooking = $donorBooking && $donorBooking->slot_id === $slot->id;
                                    @endphp
                                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                                        {{-- Time Range and Status Dot --}}
                                        <div class="flex items-center gap-3">
                                            <div class="w-2.5 h-2.5 rounded-full {{ $isFull ? 'bg-danger-500' : ($isInsideCutoff ? 'bg-neutral-300' : 'bg-success-500') }}"></div>
                                            <div>
                                                <span class="font-semibold text-sm text-neutral-900">
                                                    {{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}
                                                </span>
                                                <div class="text-xs text-neutral-500 mt-0.5">
                                                    @if ($isFull)
                                                        <span class="text-danger-600 font-medium">Fully booked</span>
                                                    @elseif ($isInsideCutoff)
                                                        <span class="text-neutral-500">Booking closed for this slot</span>
                                                    @else
                                                        <span class="text-success-700 font-medium">{{ $slot->remaining }} {{ Str::plural('spot', $slot->remaining) }} left</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Booking Action Controls --}}
                                        <div class="flex items-center justify-end">
                                            @if ($drive->isClosed())
                                                <span class="text-xs font-medium text-neutral-400 bg-neutral-100 px-3 py-1.5 rounded-lg">
                                                    Drive closed
                                                </span>
                                            @elseif ($isCurrentSlotBooking)
                                                <span class="inline-flex items-center text-xs font-bold text-primary-800 bg-primary-50 border border-primary-200 px-3 py-1.5 rounded-lg">
                                                    Your current booking
                                                </span>
                                            @elseif ($isInsideCutoff)
                                                <button type="button" disabled class="px-3.5 py-2 text-xs font-medium text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed min-h-[44px]">
                                                    Booking closed for this slot
                                                </button>
                                            @elseif ($isFull)
                                                <button type="button" disabled class="px-4 py-2 text-xs font-semibold text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed min-h-[44px]">
                                                    Full
                                                </button>
                                            @elseif (!auth()->check())
                                                <a
                                                    href="{{ route('login') }}?return_to={{ urlencode('/drives/' . $drive->id) }}"
                                                    class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 transition-colors min-h-[44px]"
                                                >
                                                    Log in to book
                                                </a>
                                            @elseif (auth()->user()->isDonor() && !auth()->user()->hasVerifiedEmail())
                                                <a
                                                    href="{{ route('verification.notice') }}"
                                                    class="inline-flex items-center justify-center px-3.5 py-2 text-xs font-semibold text-warning-800 bg-warning-100 rounded-lg hover:bg-warning-200 transition-colors min-h-[44px]"
                                                >
                                                    Verify email to book
                                                </a>
                                            @elseif (!auth()->user()->isDonor())
                                                <span class="text-xs text-neutral-400 italic">
                                                    Staff viewing
                                                </span>
                                            @elseif ($donorBooking)
                                                {{-- Donor already has a booking on this drive: show Reschedule --}}
                                                <button
                                                    type="button"
                                                    x-on:click="openRescheduleModal({{ $slot->id }}, '{{ \App\Support\DateHelper::formatDate($slot->starts_at) }}', '{{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}')"
                                                    class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors min-h-[44px]"
                                                >
                                                    Change to this time
                                                </button>
                                            @else
                                                {{-- Donor booking fresh slot --}}
                                                <button
                                                    type="button"
                                                    x-on:click="openBookModal({{ $slot->id }}, '{{ \App\Support\DateHelper::formatDate($slot->starts_at) }}', '{{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}')"
                                                    class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors shadow-xs min-h-[44px]"
                                                >
                                                    Book
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
        <x-modal name="book-slot-modal" title="Confirm Donation Appointment" maxWidth="md">
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
                        <span class="text-neutral-500 font-medium">Date & Time:</span>
                        <strong class="text-primary-800 ml-1" x-text="selectedSlot.date + ' — ' + selectedSlot.range"></strong>
                    </div>
                </div>

                <p class="text-xs text-neutral-500">
                    Please arrive 10 minutes before your appointment and carry a valid national identification or donor card.
                </p>

                <form method="POST" action="{{ route('donor.appointments.store') }}">
                    @csrf
                    <input type="hidden" name="slot_id" :value="selectedSlot.id">

                    <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                        <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'book-slot-modal')">
                            Cancel
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Confirm Booking
                        </x-button>
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
                            <strong class="text-primary-800 ml-1" x-text="rescheduleSlot.date + ' — ' + rescheduleSlot.range"></strong>
                        </div>
                        <div>
                            <span class="text-neutral-500 font-medium">Venue:</span>
                            <span class="ml-1">{{ $drive->venue }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-neutral-500">
                        Your previous time slot will be released for other donors immediately upon confirmation.
                    </p>

                    <form method="POST" :action="'/donor/appointments/{{ $donorBooking->id }}/reschedule'">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="new_slot_id" :value="rescheduleSlot.id">

                        <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'reschedule-slot-modal')">
                                Cancel
                            </x-button>
                            <x-button type="submit" variant="primary" size="sm">
                                Confirm Change
                            </x-button>
                        </div>
                    </form>
                </div>
            </x-modal>

            {{-- 3. Cancel Booking Modal --}}
            <x-modal name="cancel-booking-modal" title="Cancel Appointment" maxWidth="md">
                <div class="space-y-4">
                    <p class="text-sm text-neutral-600">
                        Are you sure you want to cancel your donation appointment for:
                    </p>

                    <div class="p-4 rounded-xl bg-danger-50/50 border border-danger-200 text-xs text-neutral-800 space-y-1">
                        <div>
                            <strong class="text-neutral-900">{{ $drive->title }}</strong>
                        </div>
                        <div>
                            <span>{{ \App\Support\DateHelper::formatDate($donorBooking->slot?->starts_at) }} ({{ \App\Support\DateHelper::formatRange($donorBooking->slot?->starts_at, $donorBooking->slot?->ends_at) }})</span>
                        </div>
                    </div>

                    <p class="text-xs text-neutral-500">
                        Your reserved place will be released immediately. You can book another session at any time.
                    </p>

                    <form method="POST" action="{{ route('donor.appointments.destroy', $donorBooking) }}">
                        @csrf
                        @method('DELETE')

                        <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'cancel-booking-modal')">
                                Keep Appointment
                            </x-button>
                            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-danger-600 text-white hover:bg-danger-700 transition-colors min-h-[44px]">
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
