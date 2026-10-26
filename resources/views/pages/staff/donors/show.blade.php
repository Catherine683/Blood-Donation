<x-layouts.staff title="{{ $donor->name }} — Donor Details">
    <div class="space-y-6 pb-12" x-data="{ correctModalOpen: false, correctModalCollapsed: false, correctRecord: null }">
        {{-- Breadcrumb & Header --}}
        <div>
            <nav class="flex items-center gap-2 text-xs text-neutral-500 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('staff.donors') }}" class="hover:text-primary-700 transition-colors">Registered Donors</a>
                <span>&rsaquo;</span>
                <span class="text-neutral-900 font-semibold" aria-current="page">{{ $donor->name }}</span>
            </nav>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-800 flex items-center justify-center font-bold text-lg">
                        {{ $donor->initials() }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-neutral-900">{{ $donor->name }}</h1>
                        <p class="text-xs text-neutral-500">{{ $donor->email }} &bull; {{ $donor->phone ?? 'No phone' }}</p>
                    </div>
                </div>
                <div>
                    <x-button :href="route('staff.donors')" variant="secondary" size="md" class="min-h-[44px]">
                        &larr; Back to Directory
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Profile Overview & Editable Staff Section (1 col) --}}
            <div class="space-y-6">
                {{-- Account Details Card --}}
                <x-card padding="md" class="space-y-3">
                    <h2 class="text-sm font-bold text-neutral-900 border-b border-neutral-100 pb-2">
                        Account Details
                    </h2>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between py-1 border-b border-neutral-50">
                            <dt class="text-neutral-500">Email Status</dt>
                            <dd class="font-medium text-neutral-800">
                                @if ($donor->hasVerifiedEmail())
                                    <span class="text-success-700">&check; Verified</span>
                                @else
                                    <span class="text-neutral-500">Unverified</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-50">
                            <dt class="text-neutral-500">Date of Birth</dt>
                            <dd class="font-medium text-neutral-800">
                                {{ $donor->date_of_birth ? \App\Support\DateHelper::formatDate($donor->date_of_birth) : 'Not specified' }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-50">
                            <dt class="text-neutral-500">Last Donated Date</dt>
                            <dd class="font-bold text-primary-700">
                                {{ $profile?->last_donated_on ? \App\Support\DateHelper::formatDate($profile->last_donated_on) : 'None recorded' }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-1">
                            <dt class="text-neutral-500">Member Since</dt>
                            <dd class="font-medium text-neutral-800">
                                {{ \App\Support\DateHelper::formatDate($donor->created_at) }}
                            </dd>
                        </div>
                    </dl>
                    <p class="text-[10px] text-neutral-400 italic">
                        Note: Last donation date is automatically derived from verified records and cannot be manually incremented.
                    </p>
                </x-card>

                {{-- Administrative Settings Form --}}
                <x-card padding="md">
                    <form method="POST" action="{{ route('staff.donors.update', $donor) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <h2 class="text-sm font-bold text-neutral-900 border-b border-neutral-100 pb-2">
                            Administrative Data
                        </h2>

                        <div>
                            <label for="blood_group" class="block text-xs font-semibold text-neutral-800 mb-1">
                                Verified Blood Group
                            </label>
                            <select
                                id="blood_group"
                                name="blood_group"
                                class="w-full text-xs rounded-lg border border-neutral-300 py-2.5 px-3 focus:ring-2 focus:ring-primary-500 min-h-[44px]"
                            >
                                <option value="" {{ empty($profile?->blood_group) ? 'selected' : '' }}>Not recorded / Unknown</option>
                                @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                    <option value="{{ $bg }}" {{ ($profile?->blood_group === $bg) ? 'selected' : '' }}>
                                        {{ $bg }}
                                    </option>
                                @endforeach
                            </select>
                            <x-error :messages="$errors->get('blood_group')" class="mt-1" />
                        </div>

                        <div>
                            <label for="notes_for_staff" class="block text-xs font-semibold text-neutral-800 mb-1">
                                Administrative note (do not enter medical details)
                            </label>
                            <textarea
                                id="notes_for_staff"
                                name="notes_for_staff"
                                rows="3"
                                maxlength="500"
                                placeholder="Logistical or administrative notes for reception staff..."
                                class="w-full text-xs rounded-lg border border-neutral-300 p-2.5 focus:ring-2 focus:ring-primary-500"
                            >{{ old('notes_for_staff', $profile?->notes_for_staff) }}</textarea>
                            <div class="text-[10px] text-neutral-400 flex justify-between pt-0.5">
                                <span>Max 500 characters</span>
                            </div>
                            <x-error :messages="$errors->get('notes_for_staff')" class="mt-1" />
                        </div>

                        <div class="pt-2">
                            <x-button type="submit" variant="primary" size="md" class="w-full justify-center min-h-[44px]">
                                Save Administrative Details
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            {{-- Right: Donation Records & Appointments (2 cols) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Verified Donation Records --}}
                <x-card padding="none" class="overflow-hidden">
                    <div class="p-4 bg-neutral-50/80 border-b border-neutral-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-neutral-900">
                            Donation History &amp; Outcomes
                        </h2>
                        <span class="text-xs text-neutral-500 font-medium">
                            {{ $donor->donationRecords->count() }} records
                        </span>
                    </div>

                    @if ($donor->donationRecords->isEmpty())
                        <div class="p-8 text-center text-xs text-neutral-400">
                            No donation outcomes recorded for this donor yet.
                        </div>
                    @else
                        <div class="divide-y divide-neutral-100">
                            @foreach ($donor->donationRecords as $record)
                                <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-neutral-50/50">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <x-badge :variant="$record->outcome->badgeVariant()" size="sm">
                                                {{ $record->outcome->label() }}
                                            </x-badge>
                                            <strong class="text-xs text-neutral-900">
                                                {{ $record->drive->title }}
                                            </strong>
                                            @if ($record->isWalkIn())
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-600">
                                                    Walk-in
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-neutral-500">
                                            Recorded on {{ \App\Support\DateHelper::formatDate($record->recorded_at) }} EAT by {{ $record->recordedBy?->name ?? 'Staff' }}
                                        </div>
                                        @if ($record->staff_note)
                                            <div class="text-xs bg-neutral-50 p-2 rounded-lg border border-neutral-200 text-neutral-700 mt-1">
                                                <span class="font-semibold text-neutral-500 text-[10px] uppercase block">Admin Note:</span>
                                                {{ $record->staff_note }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="shrink-0 pt-2 sm:pt-0">
                                        <button
                                            type="button"
                                            x-on:click="correctRecord = { id: {{ $record->id }}, driveTitle: '{{ addslashes($record->drive->title) }}', outcome: '{{ $record->outcome->value }}', note: '{{ addslashes($record->staff_note ?? '') }}' }; correctModalOpen = true"
                                            class="inline-flex items-center text-xs font-semibold text-primary-700 hover:text-primary-900 border border-neutral-200 hover:bg-neutral-50 rounded-lg px-3 py-2 min-h-[44px]"
                                        >
                                            Correct Outcome &rarr;
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                {{-- Booked Appointments --}}
                <x-card padding="none" class="overflow-hidden">
                    <div class="p-4 bg-neutral-50/80 border-b border-neutral-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-neutral-900">
                            Booked Appointments
                        </h2>
                        <span class="text-xs text-neutral-500 font-medium">
                            {{ $donor->appointments->count() }} appointments
                        </span>
                    </div>

                    @if ($donor->appointments->isEmpty())
                        <div class="p-8 text-center text-xs text-neutral-400">
                            No appointments on file for this donor.
                        </div>
                    @else
                        <div class="divide-y divide-neutral-100">
                            @foreach ($donor->appointments as $appointment)
                                <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 hover:bg-neutral-50/50">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <x-badge :variant="$appointment->status->badgeVariant()" size="sm">
                                                {{ $appointment->status->label() }}
                                            </x-badge>
                                            <strong class="text-xs text-neutral-900">
                                                {{ $appointment->slot?->drive?->title ?? 'Drive' }}
                                            </strong>
                                        </div>
                                        <div class="text-xs text-neutral-500">
                                            {{ \App\Support\DateHelper::formatDate($appointment->slot?->starts_at) }} &bull;
                                            {{ \App\Support\DateHelper::formatRange($appointment->slot?->starts_at, $appointment->slot?->ends_at) }} EAT
                                        </div>
                                        @if ($appointment->checked_in_at)
                                            <div class="text-[11px] text-info-700 font-medium">
                                                Checked in at {{ \App\Support\DateHelper::formatTime($appointment->checked_in_at) }} EAT
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-xs text-neutral-400 shrink-0">
                                        Booked {{ \App\Support\DateHelper::formatDate($appointment->created_at) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>

        {{-- MODAL: Correct Donation Outcome (Collapsible & Minimizable) --}}
        <div
            x-show="correctModalOpen"
            x-cloak
            x-on:keydown.escape.window="correctModalOpen = false; correctModalCollapsed = false"
            class="relative z-50"
        >
            {{-- Backdrop (hidden when minimized) --}}
            <div
                x-show="correctModalOpen && !correctModalCollapsed"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity"
                x-on:click="correctModalOpen = false; correctModalCollapsed = false"
                aria-hidden="true"
            ></div>

            {{-- Main Dialog (when expanded) --}}
            <div
                x-show="correctModalOpen && !correctModalCollapsed"
                class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 flex items-center justify-center pointer-events-none"
            >
                <div
                    x-show="correctModalOpen && !correctModalCollapsed"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-neutral-200 p-6 space-y-4 pointer-events-auto"
                >
                    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <h3 class="text-base font-bold text-neutral-900">Correct Donation Record</h3>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                x-on:click="correctModalCollapsed = true"
                                class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors cursor-pointer"
                                title="Minimize / Collapse dialog"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                x-on:click="correctModalOpen = false; correctModalCollapsed = false"
                                class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors cursor-pointer"
                                title="Close dialog"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-neutral-600">
                        Drive: <strong class="text-neutral-900" x-text="correctRecord ? correctRecord.driveTitle : ''"></strong>
                    </div>

                    <form
                        method="POST"
                        :action="'/staff/donors/{{ $donor->id }}/donation-records/' + (correctRecord ? correctRecord.id : '') + '/correct'"
                        class="space-y-4"
                    >
                        @csrf

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-neutral-800">Correct Outcome</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="border rounded-xl p-3 flex items-center gap-2.5 cursor-pointer min-h-[44px]" :class="correctRecord && correctRecord.outcome === 'donated' ? 'bg-success-50 border-success-400 text-success-900 font-bold' : 'bg-white border-neutral-300 text-neutral-700'">
                                    <input type="radio" name="outcome" value="donated" x-model="correctRecord.outcome" class="text-success-600 focus:ring-success-500" />
                                    <span class="text-xs">Donated</span>
                                </label>
                                <label class="border rounded-xl p-3 flex items-center gap-2.5 cursor-pointer min-h-[44px]" :class="correctRecord && correctRecord.outcome === 'deferred' ? 'bg-warning-50 border-warning-400 text-warning-900 font-bold' : 'bg-white border-neutral-300 text-neutral-700'">
                                    <input type="radio" name="outcome" value="deferred" x-model="correctRecord.outcome" class="text-warning-600 focus:ring-warning-500" />
                                    <span class="text-xs">Did not donate</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="correct-staff-note" class="block text-xs font-semibold text-neutral-800">
                                Administrative note (do not enter medical details)
                            </label>
                            <textarea
                                id="correct-staff-note"
                                name="staff_note"
                                maxlength="255"
                                rows="2"
                                x-model="correctRecord.note"
                                class="w-full text-xs rounded-lg border border-neutral-300 p-2.5 focus:ring-2 focus:ring-primary-500"
                            ></textarea>
                        </div>

                        <p class="text-[11px] text-neutral-500 italic">
                            Confirming will update the record, recalculate the donor's latest donation date, and write a security audit entry.
                        </p>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <x-button type="button" variant="secondary" size="md" x-on:click="correctModalOpen = false; correctModalCollapsed = false" class="min-h-[44px]">
                                Cancel
                            </x-button>
                            <x-button type="submit" variant="primary" size="md" class="min-h-[44px]">
                                Confirm Correction
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Docked Minimized Pill (when collapsed) --}}
            <div
                x-show="correctModalOpen && correctModalCollapsed"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-3"
                class="fixed bottom-5 right-5 z-50 bg-white border border-neutral-300 shadow-2xl rounded-2xl px-4 py-3 flex items-center gap-3.5 pointer-events-auto"
            >
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary-600"></span>
                </span>
                <div class="text-xs">
                    <div class="font-bold text-neutral-900 leading-tight">Correct Donation Record</div>
                    <div class="text-[10px] text-neutral-500 font-medium">Minimized</div>
                </div>
                <div class="flex items-center gap-1.5 ml-2 border-l border-neutral-200 pl-2.5">
                    <button
                        type="button"
                        x-on:click="correctModalCollapsed = false"
                        class="px-2.5 py-1 text-xs font-semibold bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg transition-colors cursor-pointer flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        Expand
                    </button>
                    <button
                        type="button"
                        x-on:click="correctModalOpen = false; correctModalCollapsed = false"
                        class="p-1 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors cursor-pointer"
                        title="Close"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.staff>
