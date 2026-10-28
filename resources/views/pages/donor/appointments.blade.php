<x-layouts.donor title="Appointments — Donor Portal">
    <div class="space-y-6" x-data="{ tab: 'upcoming', cancelData: { id: null, title: '', when: '', venue: '' } }">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">My Donation Appointments</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    Manage your scheduled voluntary donation sessions and view past appointment history.
                </p>
                <p class="text-xs text-primary-700 font-medium mt-1">
                    Reminders are emailed 24 hours and 2 hours before your appointment.
                </p>
            </div>
            <div>
                <x-button :href="route('drives.index')" variant="primary" size="md">
                    Browse Upcoming Drives &rarr;
                </x-button>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('status'))
            <x-alert variant="success" :title="session('status')" />
        @endif
        @if (session('error'))
            <x-alert variant="danger" :title="session('error')" />
        @endif

        {{-- Tab Controls --}}
        <div class="border-b border-neutral-200">
            <nav class="-mb-px flex space-x-6" aria-label="Appointments Tabs">
                <button
                    type="button"
                    x-on:click="tab = 'upcoming'"
                    :class="tab === 'upcoming' ? 'border-primary-600 text-primary-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 font-medium'"
                    class="py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition-colors min-h-[44px]"
                >
                    <span>Upcoming</span>
                    <span
                        :class="tab === 'upcoming' ? 'bg-primary-100 text-primary-800' : 'bg-neutral-100 text-neutral-600'"
                        class="px-2 py-0.5 rounded-full text-xs font-semibold"
                    >
                        {{ $upcomingAppointments->count() }}
                    </span>
                </button>

                <button
                    type="button"
                    x-on:click="tab = 'past'"
                    :class="tab === 'past' ? 'border-primary-600 text-primary-700 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 font-medium'"
                    class="py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition-colors min-h-[44px]"
                >
                    <span>Past</span>
                    <span
                        :class="tab === 'past' ? 'bg-primary-100 text-primary-800' : 'bg-neutral-100 text-neutral-600'"
                        class="px-2 py-0.5 rounded-full text-xs font-semibold"
                    >
                        {{ $pastAppointments->count() }}
                    </span>
                </button>
            </nav>
        </div>

        {{-- TAB 1: Upcoming Appointments --}}
        <div x-show="tab === 'upcoming'" class="space-y-4">
            @if ($upcomingAppointments->isEmpty())
                <x-empty-state
                    title="No upcoming appointments"
                    description="You do not have any scheduled blood donation appointments right now. Browse published blood drives across Uganda to reserve your next donation time."
                >
                    <x-slot:icon>
                        <svg class="w-6 h-6 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </x-slot:icon>
                    <x-button :href="route('drives.index')" variant="primary" size="md">
                        Find a Blood Drive
                    </x-button>
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($upcomingAppointments as $appointment)
                        @php
                            $slot = $appointment->slot;
                            $drive = $slot?->drive;
                        @endphp
                        <div class="bg-white rounded-xl border border-neutral-200/80 shadow-xs p-5 flex flex-col justify-between space-y-4 hover:shadow-sm hover:border-neutral-300 transition-all">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center text-xs font-semibold text-primary-700 bg-primary-50 px-2.5 py-0.5 rounded">
                                        {{ \App\Support\DateHelper::formatDate($slot?->starts_at) }}
                                    </span>
                                    <x-badge :variant="$appointment->status->badgeVariant()" size="sm">
                                        {{ $appointment->status->label() }}
                                    </x-badge>
                                </div>

                                <h3 class="text-base font-bold text-neutral-900 leading-snug">
                                    {{ $drive?->title ?? 'Blood Donation Drive' }}
                                </h3>

                                <div class="text-xs text-neutral-600 space-y-1 pt-1">
                                    <div class="flex items-center gap-1.5 font-medium text-neutral-800">
                                        <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ \App\Support\DateHelper::formatRange($slot?->starts_at, $slot?->ends_at) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate">{{ $drive?->venue }}@if($drive?->address) &bull; {{ $drive?->address }}@endif</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-neutral-100 flex items-center justify-between gap-2">
                                @if ($drive)
                                    <a
                                        href="{{ route('drives.show', $drive) }}"
                                        class="inline-flex items-center text-xs font-semibold text-primary-700 hover:text-primary-900 py-2 px-1 transition-colors min-h-[44px]"
                                    >
                                        Reschedule &rarr;
                                    </a>
                                @endif

                                <button
                                    type="button"
                                    x-on:click="cancelData = { id: {{ $appointment->id }}, title: '{{ addslashes($drive?->title ?? '') }}', when: '{{ \App\Support\DateHelper::formatDate($slot?->starts_at) }} ({{ \App\Support\DateHelper::formatRange($slot?->starts_at, $slot?->ends_at) }})', venue: '{{ addslashes($drive?->venue ?? '') }}' }; $dispatch('open-modal', 'cancel-appointment-modal')"
                                    class="inline-flex items-center text-xs font-semibold text-danger-700 hover:text-danger-900 py-2 px-2.5 rounded-lg hover:bg-danger-50 transition-colors min-h-[44px]"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- TAB 2: Past Appointments --}}
        <div x-show="tab === 'past'" class="space-y-4" x-cloak>
            @if ($pastAppointments->isEmpty())
                <x-empty-state
                    title="No past appointments"
                    description="You do not have any past donation appointments recorded yet."
                >
                    <x-slot:icon>
                        <svg class="w-6 h-6 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($pastAppointments as $appointment)
                        @php
                            $slot = $appointment->slot;
                            $drive = $slot?->drive;
                        @endphp
                        <div class="bg-white rounded-xl border border-neutral-200/80 shadow-xs p-5 flex flex-col justify-between space-y-3 opacity-90">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs text-neutral-500 font-medium">
                                        {{ \App\Support\DateHelper::formatDate($slot?->starts_at) }}
                                    </span>
                                    @php
                                        $pastBadgeLabel = match ($appointment->status) {
                                            \App\Enums\AppointmentStatus::Booked => 'Not recorded',
                                            \App\Enums\AppointmentStatus::Attended => 'Attended',
                                            \App\Enums\AppointmentStatus::NoShow => 'Missed',
                                            \App\Enums\AppointmentStatus::Cancelled => 'Cancelled',
                                        };
                                        $pastBadgeVariant = match ($appointment->status) {
                                            \App\Enums\AppointmentStatus::Booked => 'neutral',
                                            \App\Enums\AppointmentStatus::Attended => 'info',
                                            \App\Enums\AppointmentStatus::NoShow => 'neutral',
                                            \App\Enums\AppointmentStatus::Cancelled => 'danger',
                                        };
                                    @endphp
                                    <x-badge :variant="$pastBadgeVariant" size="sm">
                                        {{ $pastBadgeLabel }}
                                    </x-badge>
                                </div>

                                <h3 class="text-sm font-bold text-neutral-800 leading-snug">
                                    {{ $drive?->title ?? 'Blood Donation Drive' }}
                                </h3>

                                <p class="text-xs text-neutral-500">
                                    {{ $drive?->venue }} &bull; {{ \App\Support\DateHelper::formatRange($slot?->starts_at, $slot?->ends_at) }}
                                </p>

                                @if ($appointment->isCancelled() && $appointment->cancelled_at)
                                    <p class="text-[11px] text-danger-600 font-medium pt-1">
                                        Cancelled on {{ \App\Support\DateHelper::formatDate($appointment->cancelled_at, 'd M Y, H:i') }} EAT
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Cancel Confirmation Modal --}}
        <x-modal name="cancel-appointment-modal" title="Cancel Appointment" maxWidth="md">
            <div class="space-y-4">
                <p class="text-sm text-neutral-600">
                    Are you sure you want to cancel your donation appointment for:
                </p>

                <div class="p-4 rounded-xl bg-danger-50/50 border border-danger-200 text-xs text-neutral-800 space-y-1">
                    <strong class="text-neutral-900 block" x-text="cancelData.title"></strong>
                    <div x-text="cancelData.when"></div>
                    <div class="text-neutral-500" x-text="cancelData.venue"></div>
                </div>

                <p class="text-xs text-neutral-500">
                    Your reserved place will be released immediately. You can book another session at any time.
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
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold bg-danger-600 text-white hover:bg-danger-700 transition-colors min-h-[44px]"
                        >
                            Cancel Appointment
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>
</x-layouts.donor>
