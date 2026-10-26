<div wire:poll.30s class="space-y-6 pb-12">
    {{-- Top Navigation & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-neutral-500 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('staff.drives') }}" class="hover:text-primary-700 transition-colors">Drives</a>
                <span>&rsaquo;</span>
                <a href="{{ route('staff.drives.show', $drive) }}" class="hover:text-primary-700 transition-colors truncate max-w-[200px] sm:max-w-xs">{{ $drive->title }}</a>
                <span>&rsaquo;</span>
                <span class="text-neutral-900 font-semibold" aria-current="page">Attendance Roster</span>
            </nav>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
                    Drive Attendance Roster
                </h1>
                <x-badge :variant="$drive->status->badgeVariant()" size="sm">
                    {{ $drive->status->label() }}
                </x-badge>
            </div>
            <p class="text-xs text-neutral-600 mt-1">
                {{ $drive->venue }} &bull; {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a
                href="{{ route('staff.drives.report', $drive) }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 shadow-xs transition-colors min-h-[44px]"
            >
                <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Drive Report</span>
            </a>

            <a
                href="{{ route('staff.drives.export.roster', $drive) }}"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 shadow-xs transition-colors min-h-[44px]"
            >
                <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Export CSV</span>
            </a>

            <x-button :href="route('staff.drives.show', $drive)" variant="secondary" size="md" class="min-h-[44px]">
                &larr; Drive Details
            </x-button>

            @if ($canCloseOut)
                <x-button
                    type="button"
                    variant="danger"
                    size="md"
                    wire:click="openCloseOutModal"
                    class="min-h-[44px]"
                >
                    Close Out Drive
                </x-button>
            @endif
        </div>
    </div>

    {{-- Live Accessible Notification Region --}}
    <div aria-live="polite" class="sr-only">
        @if ($feedbackMessage)
            {{ $feedbackMessage }}
        @endif
    </div>

    @if ($feedbackMessage)
        <div class="p-4 rounded-xl border flex items-center justify-between gap-3 {{ $feedbackType === 'success' ? 'bg-success-50/80 border-success-200 text-success-900' : 'bg-danger-50/80 border-danger-200 text-danger-900' }}">
            <div class="flex items-center gap-2.5 text-sm font-medium">
                @if ($feedbackType === 'success')
                    <svg class="w-5 h-5 text-success-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg class="w-5 h-5 text-danger-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button
                type="button"
                wire:click="dismissFeedback"
                class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-black/5 transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center"
                aria-label="Dismiss message"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Common Totals Bar --}}
    <div class="space-y-3">
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Expected</span>
                <span class="text-lg sm:text-xl font-bold text-neutral-900 mt-0.5 block">{{ $summaryStats['expected'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Checked In</span>
                <span class="text-lg sm:text-xl font-bold text-info-700 mt-0.5 block">{{ $summaryStats['checked_in'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Attended</span>
                <span class="text-lg sm:text-xl font-bold text-primary-700 mt-0.5 block">{{ $summaryStats['attended'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Donated</span>
                <span class="text-lg sm:text-xl font-bold text-success-700 mt-0.5 block">{{ $summaryStats['donated'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Did not donate</span>
                <span class="text-lg sm:text-xl font-bold text-warning-700 mt-0.5 block">{{ $summaryStats['deferred'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">No-show</span>
                <span class="text-lg sm:text-xl font-bold text-neutral-600 mt-0.5 block">{{ $summaryStats['no_show'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Cancelled</span>
                <span class="text-lg sm:text-xl font-bold text-danger-700 mt-0.5 block">{{ $summaryStats['cancelled'] }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-neutral-200 text-center shadow-xs">
                <span class="block text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Walk-ins</span>
                <span class="text-lg sm:text-xl font-bold text-primary-700 mt-0.5 block">{{ $summaryStats['walk_ins'] }}</span>
            </div>
        </div>

        {{-- Live Sub-Counts (Active Queue Operational View) --}}
        <div class="bg-neutral-50 border border-neutral-200/80 rounded-xl px-4 py-2.5 flex flex-wrap items-center justify-between gap-4 text-xs">
            <span class="text-neutral-500 font-semibold uppercase tracking-wider text-[11px]">Active Drive Day Queue:</span>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-info-500 inline-block"></span>
                    <span class="text-neutral-600">Waiting to arrive:</span>
                    <span class="font-bold text-neutral-900">{{ $summaryStats['waiting_to_arrive'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-warning-500 inline-block"></span>
                    <span class="text-neutral-600">Checked in, awaiting outcome:</span>
                    <span class="font-bold text-neutral-900">{{ $summaryStats['awaiting_outcome'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Walk-in Panel --}}
    <x-card padding="md">
        <div x-data="{ expanded: false }">
            <div class="flex items-center justify-between cursor-pointer" x-on:click="expanded = !expanded">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 flex items-center justify-center font-bold text-xs">
                        +
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-neutral-900">Record Walk-in Donor</h2>
                        <p class="text-xs text-neutral-500">Record a voluntary donation for a registered donor who arrived without an appointment.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="text-xs font-semibold text-primary-700 hover:text-primary-800 p-2 min-h-[44px] min-w-[44px] flex items-center justify-center"
                    :aria-expanded="expanded"
                >
                    <span x-text="expanded ? 'Hide Panel &minus;' : 'Open Panel &plus;'"></span>
                </button>
            </div>

            <div x-show="expanded" x-collapse class="pt-4 border-t border-neutral-100 mt-4 space-y-4">
                {{-- Donor Search Input --}}
                <div class="max-w-md">
                    <label for="walkin-search" class="block text-xs font-semibold text-neutral-700 mb-1">
                        Find Registered Donor
                    </label>
                    <div class="relative">
                        <input
                            id="walkin-search"
                            type="search"
                            wire:model.live.debounce.300ms="walkInSearch"
                            placeholder="Search by full name, phone number, or email..."
                            class="w-full text-xs rounded-lg border border-neutral-300 py-2.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                        @if ($walkInSearch)
                            <button
                                type="button"
                                wire:click="$set('walkInSearch', '')"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 text-xs"
                                aria-label="Clear search"
                            >
                                &times;
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Search Results --}}
                @if (trim($walkInSearch) !== '' && $walkInResults->isEmpty())
                    <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-200 text-xs text-neutral-600">
                        No account found. Ask the donor to register at this site, then search again.
                    </div>
                @elseif ($walkInResults->isNotEmpty())
                    <div class="space-y-1.5 max-w-lg">
                        <span class="text-[11px] font-semibold uppercase text-neutral-500 block">Select Donor</span>
                        <div class="divide-y divide-neutral-100 border border-neutral-200 rounded-xl overflow-hidden bg-white">
                            @foreach ($walkInResults as $candidate)
                                <button
                                    type="button"
                                    wire:click="$set('selectedWalkInDonorId', {{ $candidate->id }})"
                                    class="w-full text-left p-2.5 text-xs flex items-center justify-between hover:bg-primary-50 transition-colors min-h-[44px] {{ $selectedWalkInDonorId === $candidate->id ? 'bg-primary-50 font-bold' : '' }}"
                                >
                                    <div>
                                        <span class="text-neutral-900 block font-semibold">{{ $candidate->name }}</span>
                                        <span class="text-neutral-500 text-[11px]">{{ $candidate->phone ?? $candidate->email }}</span>
                                    </div>
                                    @if ($selectedWalkInDonorId === $candidate->id)
                                        <span class="text-primary-700 text-xs font-bold">&check; Selected</span>
                                    @else
                                        <span class="text-neutral-400 text-xs">Select &rarr;</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Outcome Form when donor is selected --}}
                @if ($selectedWalkInDonor)
                    <div class="p-4 bg-primary-50/50 rounded-xl border border-primary-200 space-y-4 max-w-lg">
                        <div class="flex items-center justify-between border-b border-primary-100 pb-2.5">
                            <div>
                                <span class="text-xs text-neutral-500 block">Recording walk-in for:</span>
                                <strong class="text-sm text-neutral-900">{{ $selectedWalkInDonor->name }}</strong>
                                <span class="text-xs text-neutral-600 block">{{ $selectedWalkInDonor->phone }}</span>
                            </div>
                            <button
                                type="button"
                                wire:click="$set('selectedWalkInDonorId', null)"
                                class="text-xs text-neutral-500 hover:text-neutral-800 underline min-h-[44px] flex items-center"
                            >
                                Change
                            </button>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-neutral-800">Donation Outcome</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="border rounded-xl p-3 flex items-center gap-2.5 cursor-pointer min-h-[44px] {{ $walkInOutcome === 'donated' ? 'bg-success-50 border-success-400 text-success-900 font-bold' : 'bg-white border-neutral-300 text-neutral-700' }}">
                                    <input type="radio" wire:model.live="walkInOutcome" value="donated" class="text-success-600 focus:ring-success-500" />
                                    <span class="text-xs">Donated</span>
                                </label>
                                <label class="border rounded-xl p-3 flex items-center gap-2.5 cursor-pointer min-h-[44px] {{ $walkInOutcome === 'deferred' ? 'bg-warning-50 border-warning-400 text-warning-900 font-bold' : 'bg-white border-neutral-300 text-neutral-700' }}">
                                    <input type="radio" wire:model.live="walkInOutcome" value="deferred" class="text-warning-600 focus:ring-warning-500" />
                                    <span class="text-xs">Did not donate</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="walkin-staff-note" class="block text-xs font-semibold text-neutral-800">
                                Administrative note (do not enter medical details)
                            </label>
                            <textarea
                                id="walkin-staff-note"
                                wire:model.live="walkInStaffNote"
                                maxlength="255"
                                rows="2"
                                placeholder="e.g. Registered in tent B on site"
                                class="w-full text-xs rounded-lg border border-neutral-300 p-2.5 focus:ring-2 focus:ring-primary-500"
                            ></textarea>
                            <div class="text-[10px] text-neutral-500 text-right">
                                {{ strlen($walkInStaffNote) }}/255 characters
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-2">
                            <x-button
                                type="button"
                                variant="primary"
                                size="md"
                                wire:click="recordWalkIn"
                                class="min-h-[44px]"
                            >
                                Record Walk-in Outcome
                            </x-button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Filter Chips & Search Bar --}}
    <x-card padding="md" class="space-y-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            {{-- Search box --}}
            <div class="w-full md:max-w-xs relative">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or phone..."
                    class="w-full text-xs rounded-lg border border-neutral-300 py-2.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    aria-label="Search roster by donor name or phone"
                />
                @if ($search)
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 text-xs"
                        aria-label="Clear filter search"
                    >
                        &times;
                    </button>
                @endif
            </div>

            {{-- Status Filter Chips --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0" role="group" aria-label="Filter roster by status">
                @php
                    $chips = [
                        'all' => 'All',
                        'checked_in' => 'Checked In',
                        'booked' => 'Booked',
                        'donated' => 'Donated',
                        'deferred' => 'Not Donated',
                        'no_show' => 'No-Show',
                        'cancelled' => 'Cancelled',
                    ];
                @endphp
                @foreach ($chips as $key => $label)
                    <button
                        type="button"
                        wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-colors min-h-[38px] flex items-center {{ $statusFilter === $key ? 'bg-primary-700 text-white shadow-xs' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </x-card>

    {{-- Appointments Grouped By Slot --}}
    <div class="space-y-6">
        @forelse ($slots as $slot)
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
                {{-- Slot Header --}}
                <div class="px-4 py-3 bg-neutral-50/80 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                        <h3 class="text-sm font-bold text-neutral-900">
                            {{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }} EAT
                        </h3>
                        <span class="text-xs text-neutral-500">
                            ({{ \App\Support\DateHelper::formatDate($slot->starts_at) }})
                        </span>
                    </div>
                    <div class="text-xs text-neutral-600">
                        Capacity: <strong class="text-neutral-900">{{ $slot->booked_count }}/{{ $slot->capacity }} booked</strong>
                    </div>
                </div>

                {{-- Slot Appointments List --}}
                @if ($slot->appointments->isEmpty())
                    <div class="p-4 text-center text-xs text-neutral-400">
                        No appointments match current filters in this time slot.
                    </div>
                @else
                    <div class="divide-y divide-neutral-100">
                        @foreach ($slot->appointments as $appointment)
                            @php
                                $donor = $appointment->user;
                                $outcomeRecord = $appointment->donationRecord;
                                $isCheckedIn = $appointment->checked_in_at !== null;
                                $isBooked = $appointment->status === \App\Enums\AppointmentStatus::Booked;
                            @endphp
                            <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                                {{-- Donor Info --}}
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <strong class="text-sm font-bold text-neutral-900">
                                            {{ $donor->name }}
                                        </strong>

                                        @if (! $donor->hasVerifiedEmail())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-300">
                                                Unverified email
                                            </span>
                                        @endif

                                        {{-- Status / Check-in Badge --}}
                                        @if ($outcomeRecord)
                                            <x-badge :variant="$outcomeRecord->outcome->badgeVariant()" size="sm">
                                                {{ $outcomeRecord->outcome->label() }}
                                            </x-badge>
                                        @elseif ($isCheckedIn)
                                            <x-badge variant="info" size="sm">
                                                Checked In ({{ \App\Support\DateHelper::formatTime($appointment->checked_in_at) }})
                                            </x-badge>
                                        @else
                                            <x-badge :variant="$appointment->status->badgeVariant()" size="sm">
                                                {{ $appointment->status->label() }}
                                            </x-badge>
                                        @endif
                                    </div>

                                    <div class="text-xs text-neutral-600 flex items-center gap-3">
                                        @if ($donor->phone)
                                            <a href="tel:{{ $donor->phone }}" class="text-primary-700 hover:underline font-mono">
                                                {{ $donor->phone }}
                                            </a>
                                        @else
                                            <span class="text-neutral-400">No phone</span>
                                        @endif
                                        <span>&bull;</span>
                                        <span class="text-neutral-500">{{ $donor->email }}</span>
                                    </div>
                                </div>

                                {{-- Action Buttons (min 44px touch targets) --}}
                                <div class="flex items-center gap-2 flex-wrap pt-2 md:pt-0">
                                    {{-- 1. Check in --}}
                                    @if ($this->canCheckIn($appointment))
                                        <x-button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            wire:click="checkIn({{ $appointment->id }})"
                                            class="min-h-[44px] px-3 font-semibold text-primary-700 border-primary-200 hover:bg-primary-50"
                                        >
                                            Check in
                                        </x-button>
                                    @endif

                                    {{-- 2. Undo check-in --}}
                                    @if ($isCheckedIn && ! $outcomeRecord)
                                        <x-button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            wire:click="promptUndoCheckIn({{ $appointment->id }})"
                                            class="min-h-[44px] px-3 text-neutral-600 hover:bg-neutral-100"
                                        >
                                            Undo check-in
                                        </x-button>
                                    @endif

                                    {{-- 3. Record outcome --}}
                                    @if ($isBooked && ! $outcomeRecord)
                                        <x-button
                                            type="button"
                                            variant="primary"
                                            size="sm"
                                            wire:click="openOutcomeModal({{ $appointment->id }})"
                                            class="min-h-[44px] px-3 font-semibold"
                                        >
                                            Record outcome
                                        </x-button>
                                    @endif

                                    {{-- 4. No-show --}}
                                    @if ($isBooked && ! $outcomeRecord)
                                        <x-button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            wire:click="promptNoShow({{ $appointment->id }})"
                                            class="min-h-[44px] px-3 text-neutral-600 hover:bg-neutral-100"
                                        >
                                            No-show
                                        </x-button>
                                    @endif

                                    {{-- 5. Cancel booking --}}
                                    @if ($isBooked && ! $outcomeRecord)
                                        <x-button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            wire:click="promptCancel({{ $appointment->id }})"
                                            class="min-h-[44px] px-3 text-danger-700 hover:bg-danger-50 border-danger-200"
                                        >
                                            Cancel booking
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-neutral-200 p-8 text-center text-xs text-neutral-500">
                No slots configured for this blood drive.
            </div>
        @endforelse
    </div>

    {{-- MODAL: Record Outcome --}}
    @if ($showOutcomeModal && $selectedAppointment)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-neutral-900/60 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-neutral-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">Record Donation Outcome</h3>
                    <button type="button" wire:click="$set('showOutcomeModal', false)" class="text-neutral-400 hover:text-neutral-600 text-lg min-h-[44px] min-w-[44px] flex items-center justify-center">
                        &times;
                    </button>
                </div>

                <div class="text-xs text-neutral-600 space-y-1">
                    <div>Donor: <strong class="text-neutral-900">{{ $selectedAppointment->user->name }}</strong></div>
                    <div>Slot: {{ \App\Support\DateHelper::formatRange($selectedAppointment->slot?->starts_at, $selectedAppointment->slot?->ends_at) }} EAT</div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-neutral-800">Outcome</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="border rounded-xl p-3 flex items-center gap-2.5 cursor-pointer min-h-[44px] {{ $outcome === 'donated' ? 'bg-success-50 border-success-400 text-success-900 font-bold' : 'bg-white border-neutral-300 text-neutral-700' }}">
                            <input type="radio" wire:model.live="outcome" value="donated" class="text-success-600 focus:ring-success-500" />
                            <span class="text-xs">Donated</span>
                        </label>
                        <label class="border rounded-xl p-3 flex items-center gap-2.5 cursor-pointer min-h-[44px] {{ $outcome === 'deferred' ? 'bg-warning-50 border-warning-400 text-warning-900 font-bold' : 'bg-white border-neutral-300 text-neutral-700' }}">
                            <input type="radio" wire:model.live="outcome" value="deferred" class="text-warning-600 focus:ring-warning-500" />
                            <span class="text-xs">Did not donate</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="outcome-staff-note" class="block text-xs font-semibold text-neutral-800">
                        Administrative note (do not enter medical details)
                    </label>
                    <textarea
                        id="outcome-staff-note"
                        wire:model.live="staffNote"
                        maxlength="255"
                        rows="2"
                        placeholder="Optional administrative logistics note"
                        class="w-full text-xs rounded-lg border border-neutral-300 p-2.5 focus:ring-2 focus:ring-primary-500"
                    ></textarea>
                    <div class="text-[10px] text-neutral-500 text-right">
                        {{ strlen($staffNote) }}/255 characters
                    </div>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <x-button type="button" variant="secondary" size="md" wire:click="$set('showOutcomeModal', false)" class="min-h-[44px]">
                        Cancel
                    </x-button>
                    <x-button type="button" variant="primary" size="md" wire:click="saveOutcome" class="min-h-[44px]">
                        Confirm Outcome
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Destructive Actions Confirmation --}}
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-neutral-900/60 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-neutral-200 p-6 space-y-4">
                <h3 class="text-base font-bold text-neutral-900">
                    {{ $confirmTitle }}
                </h3>

                <p class="text-xs text-neutral-600 leading-relaxed">
                    {{ $confirmMessage }}
                </p>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <x-button type="button" variant="secondary" size="md" wire:click="$set('showConfirmModal', false)" class="min-h-[44px]">
                        Cancel
                    </x-button>
                    <x-button
                        type="button"
                        :variant="$confirmAction === 'cancel' ? 'danger' : 'primary'"
                        size="md"
                        wire:click="executeConfirmedAction"
                        class="min-h-[44px]"
                    >
                        Confirm Action
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: Close Out Drive --}}
    @if ($showCloseOutModal)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-neutral-900/60 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl border border-neutral-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">Close Out Blood Drive</h3>
                    <button type="button" wire:click="$set('showCloseOutModal', false)" class="text-neutral-400 hover:text-neutral-600 text-lg min-h-[44px] min-w-[44px] flex items-center justify-center">
                        &times;
                    </button>
                </div>

                @if (! empty($unresolvedCheckIns))
                    <div class="p-3 bg-danger-50 border border-danger-200 rounded-xl space-y-2">
                        <strong class="text-xs text-danger-900 block font-bold">
                            Close-out blocked: The following donors are checked in but have no recorded outcome:
                        </strong>
                        <ul class="text-xs text-danger-800 list-disc pl-4 space-y-1">
                            @foreach ($unresolvedCheckIns as $unresolved)
                                <li>
                                    <strong>{{ $unresolved['donor_name'] }}</strong>
                                    @if ($unresolved['phone'])
                                        ({{ $unresolved['phone'] }})
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p class="text-[11px] text-danger-700">
                            Please record an outcome (Donated or Did not donate) or undo check-in for these donors before closing out the drive.
                        </p>
                    </div>
                @else
                    <div class="space-y-3 text-xs text-neutral-600 leading-relaxed">
                        <p>
                            Are you ready to close out <strong class="text-neutral-900">{{ $drive->title }}</strong>?
                        </p>
                        <div class="bg-neutral-50 p-3 rounded-xl border border-neutral-200 space-y-1">
                            <div>Remaining booked appointments to be marked <strong>No-Show</strong>: <span class="font-bold text-neutral-900">{{ $pendingNoShowsCount }}</span></div>
                            <div>Drive status will change to: <strong class="text-neutral-900">Closed</strong></div>
                        </div>
                        <p class="text-neutral-500 text-[11px]">
                            This operation will run in one single atomic transaction and write a security audit entry with final drive counts.
                        </p>
                    </div>
                @endif

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <x-button type="button" variant="secondary" size="md" wire:click="$set('showCloseOutModal', false)" class="min-h-[44px]">
                        Cancel
                    </x-button>
                    @if (empty($unresolvedCheckIns))
                        <x-button type="button" variant="danger" size="md" wire:click="confirmCloseOut" class="min-h-[44px]">
                            Confirm &amp; Close Out
                        </x-button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
