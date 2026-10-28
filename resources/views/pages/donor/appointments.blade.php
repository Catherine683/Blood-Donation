<x-layouts.donor title="Appointments — Donor Portal">
    <div class="space-y-6 sm:space-y-8" x-data="{ tab: 'upcoming', cancelData: { id: null, title: '', when: '', venue: '' } }">
        {{-- System Header Banner --}}
        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-primary-50 text-primary-800 border border-primary-200/60">
                            <span class="w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
                            UBTS &bull; Clinical Appointment Registry
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 font-mono">
                            #UBTS-D{{ str_pad((string)auth()->id(), 5, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 font-sans">
                        My Donation Appointments
                    </h1>

                    <p class="text-sm text-neutral-500 max-w-2xl leading-relaxed">
                        Manage your confirmed voluntary blood donation sessions, review upcoming booking slots, or reschedule your appointment at any certified UBTS drive across Uganda.
                    </p>
                </div>

                {{-- Action Shortcuts --}}
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <x-button :href="route('drives.index')" variant="primary" size="md" class="shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Book New Appointment
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('status'))
            <x-alert variant="success" :title="session('status')" />
        @endif
        @if (session('error'))
            <x-alert variant="danger" :title="session('error')" />
        @endif

        {{-- System Tabs --}}
        <div class="border-b border-neutral-200 bg-white px-4 rounded-xl border border-neutral-200/80 shadow-2xs">
            <nav class="-mb-px flex space-x-8" aria-label="Appointments Tabs">
                <button
                    type="button"
                    x-on:click="tab = 'upcoming'"
                    :class="tab === 'upcoming' ? 'border-primary-600 text-primary-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 font-medium'"
                    class="py-4 px-1 border-b-2 text-sm flex items-center gap-2.5 transition-colors min-h-[48px]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Upcoming Scheduled Sessions</span>
                    <span
                        :class="tab === 'upcoming' ? 'bg-primary-100 text-primary-800' : 'bg-neutral-100 text-neutral-600'"
                        class="px-2 py-0.5 rounded-full text-xs font-bold"
                    >
                        {{ $upcomingAppointments->count() }}
                    </span>
                </button>

                <button
                    type="button"
                    x-on:click="tab = 'past'"
                    :class="tab === 'past' ? 'border-primary-600 text-primary-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 font-medium'"
                    class="py-4 px-1 border-b-2 text-sm flex items-center gap-2.5 transition-colors min-h-[48px]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Past &amp; Concluded Sessions</span>
                    <span
                        :class="tab === 'past' ? 'bg-primary-100 text-primary-800' : 'bg-neutral-100 text-neutral-600'"
                        class="px-2 py-0.5 rounded-full text-xs font-bold"
                    >
                        {{ $pastAppointments->count() }}
                    </span>
                </button>
            </nav>
        </div>

        {{-- TAB 1: Upcoming Appointments --}}
        <div x-show="tab === 'upcoming'" class="space-y-6">
            @if ($upcomingAppointments->isEmpty())
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-8 sm:p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900 mb-1">No Upcoming Appointments Scheduled</h3>
                    <p class="text-sm text-neutral-500 max-w-md mx-auto mb-6">
                        You do not currently have any scheduled voluntary blood donation appointments. Explore upcoming blood donation camps across Uganda to reserve your slot.
                    </p>
                    <x-button :href="route('drives.index')" variant="primary" size="md" class="shadow-sm">
                        Find a Blood Donation Drive &rarr;
                    </x-button>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach ($upcomingAppointments as $appointment)
                        @php
                            $slot = $appointment->slot;
                            $drive = $slot?->drive;
                        @endphp
                        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 flex flex-col justify-between space-y-5 hover:border-neutral-300 hover:shadow-sm transition-all relative overflow-hidden">
                            {{-- Top Status Strip --}}
                            <div class="absolute top-0 inset-x-0 h-1 bg-primary-600"></div>

                            <div class="space-y-4">
                                {{-- Card Reference Bar --}}
                                <div class="flex items-center justify-between gap-2 border-b border-neutral-100 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center text-xs font-mono font-bold text-neutral-700 bg-neutral-100 px-2 py-0.5 rounded">
                                            #UBTS-APT-{{ str_pad((string)$appointment->id, 5, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-xs text-neutral-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active Booking
                                        </span>
                                    </div>
                                    <x-badge :variant="$appointment->status->badgeVariant()" size="sm">
                                        {{ $appointment->status->label() }}
                                    </x-badge>
                                </div>

                                {{-- Drive Details --}}
                                <div>
                                    <span class="inline-flex items-center text-xs font-bold text-primary-800 bg-primary-50 px-2.5 py-1 rounded-md mb-2">
                                        <svg class="w-3.5 h-3.5 mr-1 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ \App\Support\DateHelper::formatDate($slot?->starts_at) }}
                                    </span>
                                    <h3 class="text-lg font-bold text-neutral-900 leading-snug">
                                        {{ $drive?->title ?? 'UBTS Blood Donation Session' }}
                                    </h3>
                                </div>

                                {{-- Session Time & Venue Metadata --}}
                                <div class="rounded-xl bg-neutral-50/80 border border-neutral-100 p-3.5 space-y-2 text-xs text-neutral-700">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-semibold text-neutral-900">
                                            {{ \App\Support\DateHelper::formatRange($slot?->starts_at, $slot?->ends_at) }} EAT
                                        </span>
                                        <span class="text-neutral-400 font-mono text-[11px]">(30-minute clinical window)</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-neutral-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <div>
                                            <span class="font-semibold text-neutral-900">{{ $drive?->venue }}</span>
                                            @if($drive?->address)
                                                <span class="text-neutral-500 block text-[11px] mt-0.5">{{ $drive?->address }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Clinical Pre-Donation Quick Advice --}}
                                <div class="p-3 rounded-xl bg-primary-50/40 border border-primary-100 text-xs text-primary-900 space-y-1">
                                    <div class="font-bold flex items-center gap-1.5 text-primary-800">
                                        <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        On-Site Preparation Guidelines:
                                    </div>
                                    <ul class="list-disc list-inside text-[11px] text-neutral-600 space-y-0.5 pl-1">
                                        <li>Bring a valid photo ID (National ID, Passport, or Student ID).</li>
                                        <li>Drink at least 500 mL of water or juice before arriving.</li>
                                        <li>Eat a nutritious, non-fatty meal 1-2 hours prior.</li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Actions Footer --}}
                            <div class="pt-4 border-t border-neutral-100 flex items-center justify-between gap-3">
                                @if ($drive)
                                    <a
                                        href="{{ route('drives.show', $drive) }}"
                                        class="inline-flex items-center text-xs font-bold text-neutral-700 hover:text-neutral-900 bg-neutral-100 hover:bg-neutral-200/80 px-3.5 py-2 rounded-lg transition-colors min-h-[40px]"
                                    >
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Change Slot / Reschedule
                                    </a>
                                @endif

                                <button
                                    type="button"
                                    x-on:click="cancelData = { id: {{ $appointment->id }}, title: '{{ addslashes($drive?->title ?? 'UBTS Blood Drive') }}', when: '{{ \App\Support\DateHelper::formatDate($slot?->starts_at) }} ({{ \App\Support\DateHelper::formatRange($slot?->starts_at, $slot?->ends_at) }})', venue: '{{ addslashes($drive?->venue ?? 'UBTS Collection Center') }}' }; $dispatch('open-modal', 'cancel-appointment-modal')"
                                    class="inline-flex items-center text-xs font-semibold text-danger-700 hover:text-danger-900 bg-danger-50 hover:bg-danger-100 px-3.5 py-2 rounded-lg transition-colors min-h-[40px]"
                                >
                                    Cancel Booking
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- TAB 2: Past Appointments --}}
        <div x-show="tab === 'past'" class="space-y-6" x-cloak>
            @if ($pastAppointments->isEmpty())
                <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-8 sm:p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900 mb-1">No Past Appointments Recorded</h3>
                    <p class="text-sm text-neutral-500 max-w-md mx-auto">
                        Historical appointment logs and past blood drive sessions will be displayed here once concluded.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($pastAppointments as $appointment)
                        @php
                            $slot = $appointment->slot;
                            $drive = $slot?->drive;
                        @endphp
                        <div class="bg-white rounded-2xl border border-neutral-200/80 shadow-xs p-5 flex flex-col justify-between space-y-3">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2 border-b border-neutral-100 pb-2">
                                    <span class="text-xs text-neutral-500 font-mono font-semibold">
                                        #UBTS-APT-{{ str_pad((string)$appointment->id, 5, '0', STR_PAD_LEFT) }}
                                    </span>
                                    @php
                                        $pastBadgeLabel = match ($appointment->status) {
                                            \App\Enums\AppointmentStatus::Booked => 'Concluded Session',
                                            \App\Enums\AppointmentStatus::Attended => 'Attended',
                                            \App\Enums\AppointmentStatus::NoShow => 'Missed',
                                            \App\Enums\AppointmentStatus::Cancelled => 'Cancelled',
                                        };
                                        $pastBadgeVariant = match ($appointment->status) {
                                            \App\Enums\AppointmentStatus::Booked => 'neutral',
                                            \App\Enums\AppointmentStatus::Attended => 'success',
                                            \App\Enums\AppointmentStatus::NoShow => 'neutral',
                                            \App\Enums\AppointmentStatus::Cancelled => 'danger',
                                        };
                                    @endphp
                                    <x-badge :variant="$pastBadgeVariant" size="sm">
                                        {{ $pastBadgeLabel }}
                                    </x-badge>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-xs text-neutral-500 font-medium block">
                                        {{ \App\Support\DateHelper::formatDate($slot?->starts_at) }}
                                    </span>
                                    <h3 class="text-base font-bold text-neutral-900 leading-snug">
                                        {{ $drive?->title ?? 'UBTS Blood Donation Session' }}
                                    </h3>
                                    <p class="text-xs text-neutral-500">
                                        {{ $drive?->venue }} &bull; {{ \App\Support\DateHelper::formatRange($slot?->starts_at, $slot?->ends_at) }}
                                    </p>
                                </div>

                                @if ($appointment->isCancelled() && $appointment->cancelled_at)
                                    <div class="pt-2">
                                        <span class="inline-flex items-center gap-1 text-[11px] text-danger-700 bg-danger-50 px-2 py-0.5 rounded font-medium">
                                            Cancelled on {{ \App\Support\DateHelper::formatDate($appointment->cancelled_at, 'd M Y, H:i') }} EAT
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Cancel Confirmation Modal --}}
        <x-modal name="cancel-appointment-modal" title="Cancel Voluntary Donation Booking" maxWidth="md">
            <div class="space-y-4">
                <p class="text-sm text-neutral-600">
                    Are you sure you want to cancel your scheduled voluntary blood donation session?
                </p>

                <div class="p-4 rounded-xl bg-danger-50/50 border border-danger-200 text-xs text-neutral-800 space-y-1.5">
                    <div class="font-bold text-neutral-900 text-sm" x-text="cancelData.title"></div>
                    <div class="flex items-center gap-1.5 text-neutral-700">
                        <svg class="w-3.5 h-3.5 text-neutral-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span x-text="cancelData.when"></span>
                    </div>
                    <div class="flex items-center gap-1.5 text-neutral-500">
                        <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        <span x-text="cancelData.venue"></span>
                    </div>
                </div>

                <p class="text-xs text-neutral-500 leading-relaxed">
                    Your reserved slot will be released back into the UBTS public pool so another voluntary donor can book it. You may book another session at any time.
                </p>

                <form method="POST" :action="'/donor/appointments/' + (cancelData.id || '')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="appointment_id" :value="cancelData.id">

                    <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                        <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'cancel-appointment-modal')">
                            Keep Appointment
                        </x-button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold bg-danger-600 text-white hover:bg-danger-700 transition-colors min-h-[44px] shadow-xs cursor-pointer"
                        >
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>
</x-layouts.donor>