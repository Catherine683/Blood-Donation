<x-layouts.staff :title="$drive->title . ' — Staff Portal'">
    <div class="space-y-6" x-data="driveManagement()">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center text-xs font-medium text-neutral-500 gap-1.5" aria-label="Breadcrumb">
            <a href="{{ route('staff.drives') }}" class="hover:text-neutral-800 transition-colors">Drives</a>
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
        @if ($errors->any())
            <x-alert variant="danger" title="Action failed">
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        {{-- Drive Header Card --}}
        <x-card padding="lg">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                {{-- Left: Details --}}
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-badge :variant="$drive->status->badgeVariant()" size="md">
                            {{ $drive->status->label() }}
                        </x-badge>

                        @if ($drive->isPast())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-neutral-100 text-neutral-600 border border-neutral-200">
                                Past Drive
                            </span>
                        @endif

                        <span class="text-xs text-neutral-500">
                            Created {{ $drive->created_at?->diffForHumans() }} by {{ $drive->creator?->name ?? 'Staff' }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
                        {{ $drive->title }}
                    </h1>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6 text-sm text-neutral-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span><strong>Venue:</strong> {{ $drive->venue }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span><strong>Dates (EAT):</strong> {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}</span>
                        </div>
                        @if ($drive->address)
                            <div class="flex items-center gap-2 sm:col-span-2 text-xs text-neutral-600">
                                <span class="font-medium text-neutral-500">Address:</span> {{ $drive->address }}
                            </div>
                        @endif
                    </div>

                    @if ($drive->description)
                        <div class="pt-2 text-sm text-neutral-600 border-t border-neutral-100 max-w-2xl whitespace-pre-line">
                            {{ $drive->description }}
                        </div>
                    @endif
                </div>

                {{-- Right: Actions Toolbar --}}
                <div class="flex flex-wrap lg:flex-col items-stretch gap-2.5 shrink-0 min-w-[210px]">
                    {{-- Edit Action (Prominent Full-Page CRUD Edit) --}}
                    @if ($drive->isPast())
                        <button type="button" disabled class="w-full py-2.5 px-3 text-xs font-medium text-neutral-400 bg-neutral-100 rounded-xl cursor-not-allowed text-center" title="Past drives cannot be edited">
                            Edit Drive Details (Past Drive)
                        </button>
                    @else
                        <a
                            href="{{ route('staff.drives.edit', $drive) }}"
                            class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-neutral-900 text-white hover:bg-neutral-800 transition-colors shadow-xs"
                            title="Open full edit page to modify venue, schedule, or description"
                        >
                            <svg class="w-4 h-4 text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Drive Details
                        </a>
                    @endif

                    {{-- Attendance Roster Link --}}
                    <x-button :href="route('staff.drives.roster', $drive)" variant="primary" size="sm" class="justify-center font-semibold rounded-xl">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Attendance Roster
                    </x-button>

                    {{-- Drive Performance Report Link --}}
                    <x-button :href="route('staff.drives.report', $drive)" variant="secondary" size="sm" class="justify-center font-semibold rounded-xl">
                        <svg class="w-4 h-4 mr-1.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Drive Report
                    </x-button>

                    {{-- Public Page Preview Link --}}
                    <x-button :href="route('drives.show', $drive)" target="_blank" variant="secondary" size="sm" class="justify-center rounded-xl">
                        <svg class="w-4 h-4 mr-1.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Public Page
                    </x-button>

                    {{-- Publish Action (Draft) --}}
                    @if ($drive->isDraft())
                        @php
                            $hasUpcomingSlots = $drive->slots()->where('starts_at', '>=', now('UTC'))->exists();
                            $canPublish = !$drive->isPast() && $hasUpcomingSlots;
                        @endphp
                        @if ($canPublish)
                            <button
                                type="button"
                                x-on:click="$dispatch('open-modal', 'publish-drive-modal')"
                                class="w-full inline-flex items-center justify-center px-3.5 py-2 rounded-lg text-sm font-semibold bg-success-600 text-white hover:bg-success-700 shadow-xs transition-colors"
                            >
                                Publish Drive
                            </button>
                        @else
                            <div class="space-y-1">
                                <button type="button" disabled class="w-full py-2 px-3 text-xs font-semibold text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed text-center">
                                    Publish Drive
                                </button>
                                <p class="text-[11px] text-neutral-500 text-center">
                                    {{ $drive->isPast() ? 'Cannot publish past drives.' : 'Requires at least 1 upcoming slot.' }}
                                </p>
                            </div>
                        @endif
                    @endif

                    {{-- Send Announcement Action (Published) --}}
                    @if ($drive->isPublished())
                        @if ($announcementStats['total'] > 0)
                            <div class="rounded-lg bg-neutral-50 p-2.5 border border-neutral-200 text-xs text-neutral-600 space-y-1">
                                <div class="font-semibold text-neutral-800 flex items-center justify-between">
                                    <span>Announcement:</span>
                                    <span class="text-primary-700">{{ $announcementStats['sent'] }}/{{ $announcementStats['total'] }} sent</span>
                                </div>
                                <div class="text-[11px] text-neutral-500">
                                    Queued: {{ $announcementStats['queued'] }} &bull; Skipped: {{ $announcementStats['skipped'] }} &bull; Failed: {{ $announcementStats['failed'] }}
                                </div>
                            </div>
                        @else
                            <button
                                type="button"
                                x-on:click="$dispatch('open-modal', 'send-announcement-modal')"
                                class="w-full inline-flex items-center justify-center px-3.5 py-2 rounded-lg text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 shadow-xs transition-colors"
                            >
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                                Send Announcement
                            </button>
                        @endif
                    @endif

                    {{-- Close Action (Published) --}}
                    @if ($drive->isPublished())
                        <button
                            type="button"
                            x-on:click="$dispatch('open-modal', 'close-drive-modal')"
                            class="w-full inline-flex items-center justify-center px-3.5 py-2 rounded-lg text-sm font-semibold bg-danger-600 text-white hover:bg-danger-700 shadow-xs transition-colors"
                        >
                            Close Drive
                        </button>
                    @endif

                    {{-- Reopen Action (Closed) --}}
                    @if ($drive->isClosed())
                        @if (!$drive->isPast())
                            <button
                                type="button"
                                x-on:click="$dispatch('open-modal', 'reopen-drive-modal')"
                                class="w-full inline-flex items-center justify-center px-3.5 py-2 rounded-lg text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 shadow-xs transition-colors"
                            >
                                Reopen Drive
                            </button>
                        @else
                            <button type="button" disabled class="w-full py-2 px-3 text-xs font-semibold text-neutral-400 bg-neutral-100 rounded-lg cursor-not-allowed text-center" title="Cannot reopen an ended drive">
                                Reopen (Drive Ended)
                            </button>
                        @endif
                    @endif

                    {{-- Delete Action (Draft only) --}}
                    @if ($drive->isDraft())
                        <button
                            type="button"
                            x-on:click="$dispatch('open-modal', 'delete-drive-modal')"
                            class="w-full inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-medium text-danger-700 hover:text-danger-800 hover:bg-danger-50 transition-colors"
                        >
                            Delete Draft
                        </button>
                    @else
                        <div class="text-[11px] text-neutral-400 text-center italic py-1">
                            Only draft drives can be deleted.
                        </div>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Drive Announcement Progress Banner --}}
        @if ($announcementStats['total'] > 0)
            <x-card padding="md">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-bold text-neutral-900">Drive Announcement Progress</h2>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Automated 10-minute dispatch batches respecting the Brevo rolling 24h budget.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="px-2.5 py-1 rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200">
                            Queued: {{ $announcementStats['queued'] }}
                        </span>
                        <span class="px-2.5 py-1 rounded-md bg-success-50 text-success-700 border border-success-200">
                            Sent: {{ $announcementStats['sent'] }}
                        </span>
                        <span class="px-2.5 py-1 rounded-md bg-warning-50 text-warning-700 border border-warning-200">
                            Skipped: {{ $announcementStats['skipped'] }}
                        </span>
                        <span class="px-2.5 py-1 rounded-md bg-danger-50 text-danger-700 border border-danger-200">
                            Failed: {{ $announcementStats['failed'] }}
                        </span>
                    </div>
                </div>
            </x-card>
        @endif

        {{-- Slots Section --}}
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-neutral-900">
                        Donation Time Slots
                    </h2>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        {{ $slots->count() }} total slots &bull;
                        {{ $slots->sum('capacity') }} total capacity &bull;
                        {{ $slots->sum('booked_count') }} booked &bull;
                        {{ $slots->sum(fn($s) => $s->remaining) }} spots remaining
                    </p>
                </div>

                @if (!$drive->isPast())
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            x-on:click="generatorExpanded = !generatorExpanded"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border transition-all cursor-pointer"
                            :class="generatorExpanded ? 'bg-primary-50 border-primary-300 text-primary-800' : 'bg-white border-neutral-300 text-neutral-800 hover:bg-neutral-50'"
                        >
                            <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span x-text="generatorExpanded ? 'Collapse Generator' : 'Auto-Generate Slots'">Auto-Generate Slots</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': generatorExpanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <x-button
                            type="button"
                            variant="primary"
                            size="sm"
                            x-on:click="$dispatch('open-modal', 'add-slot-modal')"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Single Slot
                        </x-button>
                    </div>
                @endif
            </div>

            {{-- Inline Collapsible Slot Generator Panel --}}
            @if (!$drive->isPast())
                @php
                    $start = \Carbon\CarbonImmutable::parse($drive->starts_on);
                    $end = \Carbon\CarbonImmutable::parse($drive->ends_on);
                    $driveDates = [];
                    for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                        $driveDates[] = $date->format('Y-m-d');
                    }
                @endphp
                <div
                    x-show="generatorExpanded"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    x-cloak
                    class="bg-white rounded-2xl border border-neutral-300 shadow-sm p-5 sm:p-6 space-y-4"
                >
                    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                                Inline Slot Generator
                            </h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                Generate consecutive time slots across drive dates with optional lunch break skipping.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                x-on:click="$dispatch('open-modal', 'generator-modal')"
                                class="text-xs text-neutral-600 hover:text-neutral-900 px-2.5 py-1 rounded-lg border border-neutral-200 hover:bg-neutral-50 transition cursor-pointer"
                                title="Open as floating dialog"
                            >
                                Open in Dialog
                            </button>
                            <button
                                type="button"
                                x-on:click="generatorExpanded = false"
                                class="text-xs font-semibold text-neutral-500 hover:text-neutral-800 px-2.5 py-1 rounded-lg hover:bg-neutral-100 transition flex items-center gap-1 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                                Collapse
                            </button>
                        </div>
                    </div>

                    <form id="inline-generator-form" method="POST" action="{{ route('staff.drives.slots.generate', $drive) }}" class="space-y-4">
                        @csrf
                        {{-- Dates selection --}}
                        <div>
                            <label class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                                Select Drive Date(s)
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($driveDates as $dDate)
                                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-300 bg-neutral-50/50 hover:bg-white text-xs font-medium cursor-pointer">
                                        <input type="checkbox" name="dates[]" value="{{ $dDate }}" class="rounded text-primary-600 focus:ring-primary-500" checked>
                                        <span>{{ \Carbon\CarbonImmutable::parse($dDate)->format('D, d M') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Hours and Length --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Start Time (EAT)</label>
                                <input type="time" name="start_time" x-model="generator.startTime" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">End Time (EAT)</label>
                                <input type="time" name="end_time" x-model="generator.endTime" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Slot Length</label>
                                <select name="slot_length" x-model="generator.slotLength" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                                    <option value="10">10 minutes</option>
                                    <option value="15">15 minutes</option>
                                    <option value="20">20 minutes</option>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes</option>
                                </select>
                            </div>
                        </div>

                        {{-- Capacity & Break Window --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Capacity / Slot</label>
                                <input type="number" name="capacity" x-model="generator.capacity" min="1" max="100" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Break Start (Optional)</label>
                                <input type="time" name="break_start_time" x-model="generator.breakStart" placeholder="e.g. 13:00" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Break End (Optional)</label>
                                <input type="time" name="break_end_time" x-model="generator.breakEnd" placeholder="e.g. 14:00" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                            </div>
                        </div>

                        {{-- Preview & Action Buttons --}}
                        <div class="pt-2 flex flex-wrap justify-between items-center gap-2">
                            <button
                                type="button"
                                x-on:click="runPreview('inline-generator-form')"
                                :disabled="loadingPreview"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-neutral-900 text-white hover:bg-neutral-800 disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                <span x-show="!loadingPreview">Generate Preview</span>
                                <span x-show="loadingPreview" x-cloak>Calculating preview...</span>
                            </button>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    x-on:click="generatorExpanded = false"
                                    class="px-3 py-2 rounded-lg text-xs font-semibold text-neutral-600 hover:bg-neutral-100 transition-colors cursor-pointer"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!previewData || previewData.total_to_create === 0"
                                    class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                >
                                    Confirm & Save Slots
                                </button>
                            </div>
                        </div>

                        {{-- Live Preview Section --}}
                        <template x-if="previewData">
                            <div class="mt-4 p-4 rounded-xl bg-neutral-50 border border-neutral-200 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm font-bold text-neutral-900">
                                        Preview: <span class="text-primary-700" x-text="previewData.total_to_create"></span> slots to be created
                                    </div>
                                    <div class="text-xs text-neutral-500">
                                        Skipped: <strong x-text="previewData.total_skipped"></strong>
                                        (<span x-text="previewData.skipped_collisions"></span> collisions,
                                        <span x-text="previewData.skipped_breaks"></span> break window,
                                        <span x-text="previewData.skipped_past"></span> past)
                                    </div>
                                </div>

                                <div class="max-h-48 overflow-y-auto border border-neutral-200 rounded-lg bg-white p-2 text-xs divide-y divide-neutral-100">
                                    <template x-for="(slot, idx) in previewData.slots" :key="idx">
                                        <div class="py-1.5 px-2 flex justify-between items-center text-neutral-700">
                                            <span class="font-medium" x-text="slot.formatted_date + ' — ' + slot.display_range"></span>
                                            <span class="text-neutral-500" x-text="'Capacity: ' + slot.capacity"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="previewError">
                            <div class="p-3 bg-danger-50 border border-danger-200 rounded-lg text-xs text-danger-700 font-medium" x-text="previewError"></div>
                        </template>
                    </form>
                </div>
            @endif
            </div>

            @if ($groupedSlots->isEmpty())
                <x-empty-state
                    title="No slots configured yet"
                    description="Donors cannot view or book appointment slots until you define them. You can generate a full schedule automatically or add individual slots."
                >
                    <x-slot:icon>
                        <svg class="w-6 h-6 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                    @if (!$drive->isPast())
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <x-button
                                type="button"
                                variant="primary"
                                size="md"
                                x-on:click="$dispatch('open-modal', 'generator-modal')"
                            >
                                Open Slot Generator
                            </x-button>
                            <x-button
                                type="button"
                                variant="secondary"
                                size="md"
                                x-on:click="$dispatch('open-modal', 'add-slot-modal')"
                            >
                                Add Single Slot
                            </x-button>
                        </div>
                    @endif
                </x-empty-state>
            @else
                <div class="space-y-6">
                    @foreach ($groupedSlots as $dateStr => $daySlots)
                        @php
                            $firstSlot = $daySlots->first();
                            $dayLabel = \App\Support\DateHelper::formatDate($firstSlot->starts_at, 'l, d F Y');
                        @endphp
                        <div class="bg-white rounded-xl shadow-xs border border-neutral-200 overflow-hidden">
                            <div class="px-5 py-3.5 bg-neutral-50/80 border-b border-neutral-200 flex items-center justify-between">
                                <h3 class="font-semibold text-sm text-neutral-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $dayLabel }}
                                </h3>
                                <span class="text-xs font-medium text-neutral-600 bg-white border border-neutral-200 rounded-full px-2.5 py-0.5">
                                    {{ $daySlots->count() }} slots &bull; {{ $daySlots->sum('capacity') }} spots
                                </span>
                            </div>

                            {{-- Slot Items (Optimized for 360px+ screens) --}}
                            <div class="divide-y divide-neutral-100">
                                @foreach ($daySlots as $slot)
                                    <div class="p-3.5 sm:px-5 sm:py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                                        {{-- Time Range & Status --}}
                                        <div class="flex items-center gap-3">
                                            <div class="w-2.5 h-2.5 rounded-full {{ $slot->isPast() ? 'bg-neutral-300' : ($slot->remaining > 0 ? 'bg-success-500' : 'bg-danger-500') }}"></div>
                                            <div>
                                                <div class="font-semibold text-sm text-neutral-900">
                                                    {{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}
                                                </div>
                                                @if ($slot->isPast())
                                                    <span class="text-[11px] text-neutral-400 font-medium">Slot time has passed</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Stats & Controls --}}
                                        <div class="flex items-center justify-between sm:justify-end gap-4 text-xs">
                                            <div class="flex items-center gap-3 text-neutral-600">
                                                <span>Capacity: <strong class="text-neutral-900">{{ $slot->capacity }}</strong></span>
                                                <span>Booked: <strong class="text-neutral-900">{{ $slot->booked_count }}</strong></span>
                                                <span class="font-medium {{ $slot->remaining > 0 ? 'text-success-700' : 'text-danger-700' }}">
                                                    Remaining: {{ $slot->remaining }}
                                                </span>
                                            </div>

                                            @if (!$drive->isPast())
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    {{-- Edit Button --}}
                                                    <button
                                                        type="button"
                                                        x-on:click="openEditSlotModal({{ $slot->id }}, '{{ \App\Support\DateHelper::formatTime($slot->starts_at) }}', '{{ \App\Support\DateHelper::formatTime($slot->ends_at) }}', {{ $slot->capacity }}, {{ $slot->booked_count }}, '{{ $slot->starts_at->format('Y-m-d') }}')"
                                                        class="p-1.5 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                                                        title="Edit slot"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>

                                                    {{-- Delete Button --}}
                                                    @if ($slot->booked_count > 0)
                                                        <span class="p-1.5 text-neutral-300 cursor-not-allowed" title="Cannot delete slot with existing bookings">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <button
                                                            type="button"
                                                            x-on:click="openDeleteSlotModal({{ $slot->id }}, '{{ \App\Support\DateHelper::formatRange($slot->starts_at, $slot->ends_at) }}')"
                                                            class="p-1.5 rounded-lg text-danger-600 hover:text-danger-800 hover:bg-danger-50 transition-colors"
                                                            title="Delete slot"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
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

        {{-- ================= MODALS ================= --}}

        {{-- Send Announcement Confirmation Modal --}}
        <x-modal name="send-announcement-modal" title="Send Drive Announcement" maxWidth="md">
            <div class="space-y-4">
                <p class="text-sm text-neutral-600 leading-relaxed">
                    Announce <strong class="text-neutral-900">{{ $drive->title }}</strong> to registered voluntary blood donors.
                </p>

                <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 text-sm space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Recipients who have opted in:</span>
                        <span class="font-bold text-neutral-900">{{ $optedInRecipientsCount }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Announcements allowed right now:</span>
                        <span class="font-bold text-neutral-900">{{ $remainingBudget }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Estimated 10-minute dispatch runs:</span>
                        <span class="font-bold text-neutral-900">{{ $estimatedRuns }}</span>
                    </div>
                </div>

                <p class="text-xs text-neutral-500 leading-normal">
                    This announcement can be sent once for this blood drive. Batches are dispatched automatically every 10 minutes to protect our Brevo sending reputation and daily allowance.
                </p>
            </div>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'send-announcement-modal')">
                    Cancel
                </x-button>
                <form method="POST" action="{{ route('staff.drives.announcement', $drive) }}">
                    @csrf
                    <x-button type="submit" variant="primary" size="sm" :disabled="$optedInRecipientsCount === 0 || $remainingBudget <= 0">
                        Queue Announcement
                    </x-button>
                </form>
            </x-slot:footer>
        </x-modal>

        {{-- 1. Publish Drive Confirmation Modal --}}
        <x-modal name="publish-drive-modal" title="Publish Blood Donation Drive" maxWidth="md">
            <p class="text-sm text-neutral-600">
                Are you sure you want to publish <strong class="text-neutral-900">{{ $drive->title }}</strong>?
            </p>
            <p class="text-xs text-neutral-500 mt-2">
                Once published, this drive and its upcoming time slots will immediately become visible to the public on the UBTS drives directory.
            </p>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'publish-drive-modal')">
                    Cancel
                </x-button>
                <form method="POST" action="{{ route('staff.drives.publish', $drive) }}">
                    @csrf
                    <x-button type="submit" variant="primary" size="sm">
                        Confirm & Publish
                    </x-button>
                </form>
            </x-slot:footer>
        </x-modal>

        {{-- 2. Close Drive Confirmation Modal --}}
        <x-modal name="close-drive-modal" title="Close Blood Drive" maxWidth="md">
            <p class="text-sm text-neutral-600">
                Are you sure you want to close <strong class="text-neutral-900">{{ $drive->title }}</strong>?
            </p>
            <p class="text-xs text-neutral-500 mt-2">
                Closing removes the drive from the public schedule. Anyone navigating directly to its link will see a "This drive is closed" notice. If the drive has not ended, you can reopen it later.
            </p>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'close-drive-modal')">
                    Cancel
                </x-button>
                <form method="POST" action="{{ route('staff.drives.close', $drive) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-danger-600 text-white hover:bg-danger-700 transition-colors">
                        Close Drive
                    </button>
                </form>
            </x-slot:footer>
        </x-modal>

        {{-- 3. Reopen Drive Confirmation Modal --}}
        <x-modal name="reopen-drive-modal" title="Reopen Blood Drive" maxWidth="md">
            <p class="text-sm text-neutral-600">
                Reopen <strong class="text-neutral-900">{{ $drive->title }}</strong>?
            </p>
            <p class="text-xs text-neutral-500 mt-2">
                This will set the status back to Published and restore it to the public schedule.
            </p>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'reopen-drive-modal')">
                    Cancel
                </x-button>
                <form method="POST" action="{{ route('staff.drives.reopen', $drive) }}">
                    @csrf
                    <x-button type="submit" variant="primary" size="sm">
                        Reopen Drive
                    </x-button>
                </form>
            </x-slot:footer>
        </x-modal>

        {{-- 4. Delete Draft Drive Confirmation Modal --}}
        <x-modal name="delete-drive-modal" title="Delete Draft Drive" maxWidth="md">
            <p class="text-sm text-neutral-600">
                Are you sure you want to delete <strong class="text-neutral-900">{{ $drive->title }}</strong>?
            </p>
            <p class="text-xs text-danger-600 mt-2">
                This action is irreversible and will delete the draft drive and any slots configured for it.
            </p>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'delete-drive-modal')">
                    Cancel
                </x-button>
                <form method="POST" action="{{ route('staff.drives.destroy', $drive) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-danger-600 text-white hover:bg-danger-700 transition-colors">
                        Delete Permanently
                    </button>
                </form>
            </x-slot:footer>
        </x-modal>

        {{-- 5. Add Single Slot Modal --}}
        <x-modal name="add-slot-modal" title="Add Single Time Slot" maxWidth="md">
            <form method="POST" action="{{ route('staff.drives.slots.store', $drive) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="slot-date" class="block text-xs font-semibold text-neutral-700 mb-1">
                        Date (EAT) <span class="text-danger-600">*</span>
                    </label>
                    <input
                        type="date"
                        name="date"
                        id="slot-date"
                        value="{{ $drive->starts_on->format('Y-m-d') }}"
                        min="{{ $drive->starts_on->format('Y-m-d') }}"
                        max="{{ $drive->ends_on->format('Y-m-d') }}"
                        required
                        class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none"
                    >
                    <p class="text-[11px] text-neutral-500 mt-1">Must fall within drive dates ({{ $drive->starts_on->format('Y-m-d') }} to {{ $drive->ends_on->format('Y-m-d') }}).</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="slot-start-time" class="block text-xs font-semibold text-neutral-700 mb-1">
                            Start Time (EAT) <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="time"
                            name="start_time"
                            id="slot-start-time"
                            value="09:00"
                            required
                            class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label for="slot-end-time" class="block text-xs font-semibold text-neutral-700 mb-1">
                            End Time (EAT) <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="time"
                            name="end_time"
                            id="slot-end-time"
                            value="09:30"
                            required
                            class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none"
                        >
                    </div>
                </div>

                <div>
                    <label for="slot-capacity" class="block text-xs font-semibold text-neutral-700 mb-1">
                        Capacity (Donors) <span class="text-danger-600">*</span>
                    </label>
                    <input
                        type="number"
                        name="capacity"
                        id="slot-capacity"
                        value="10"
                        min="1"
                        max="100"
                        required
                        class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none"
                    >
                    <p class="text-[11px] text-neutral-500 mt-1">Between 1 and 100 donors simultaneously.</p>
                </div>

                <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'add-slot-modal')">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="sm">
                        Save Slot
                    </x-button>
                </div>
            </form>
        </x-modal>

        {{-- 6. Edit Slot Modal --}}
        <x-modal name="edit-slot-modal" title="Edit Time Slot" maxWidth="md">
            <form :action="'/staff/slots/' + editSlot.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <template x-if="editSlot.bookedCount > 0">
                    <div class="p-3 bg-warning-50 border border-warning-200 rounded-lg text-xs text-warning-800">
                        <strong>Note:</strong> This slot has <span x-text="editSlot.bookedCount"></span> booked donor(s). Start and end times are locked and cannot be changed. Capacity cannot be reduced below <span x-text="editSlot.bookedCount"></span>.
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1">Date (EAT)</label>
                    <input
                        type="date"
                        name="date"
                        x-model="editSlot.date"
                        :disabled="editSlot.bookedCount > 0"
                        min="{{ $drive->starts_on->format('Y-m-d') }}"
                        max="{{ $drive->ends_on->format('Y-m-d') }}"
                        class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none disabled:bg-neutral-100 disabled:text-neutral-500"
                    >
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1">Start Time (EAT)</label>
                        <input
                            type="time"
                            name="start_time"
                            x-model="editSlot.startTime"
                            :disabled="editSlot.bookedCount > 0"
                            class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none disabled:bg-neutral-100 disabled:text-neutral-500"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1">End Time (EAT)</label>
                        <input
                            type="time"
                            name="end_time"
                            x-model="editSlot.endTime"
                            :disabled="editSlot.bookedCount > 0"
                            class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none disabled:bg-neutral-100 disabled:text-neutral-500"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1">Capacity</label>
                    <input
                        type="number"
                        name="capacity"
                        x-model="editSlot.capacity"
                        :min="Math.max(1, editSlot.bookedCount)"
                        max="100"
                        required
                        class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none"
                    >
                </div>

                <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'edit-slot-modal')">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="sm">
                        Update Slot
                    </x-button>
                </div>
            </form>
        </x-modal>

        {{-- 7. Delete Slot Confirmation Modal --}}
        <x-modal name="delete-slot-modal" title="Delete Time Slot" maxWidth="sm">
            <p class="text-sm text-neutral-600">
                Are you sure you want to delete the slot: <strong x-text="deleteSlot.range" class="text-neutral-900"></strong>?
            </p>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'delete-slot-modal')">
                    Cancel
                </x-button>
                <form :action="'/staff/slots/' + deleteSlot.id" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-danger-600 text-white hover:bg-danger-700 transition-colors">
                        Delete Slot
                    </button>
                </form>
            </x-slot:footer>
        </x-modal>

        {{-- 8. Slot Generator Modal (with Live Preview Step) --}}
        <x-modal name="generator-modal" title="Slot Generator" maxWidth="2xl">
            <div class="space-y-4">
                <p class="text-xs text-neutral-600">
                    Generate consecutive time slots across drive dates with optional lunch break skipping. Preview before applying.
                </p>

                <form id="generator-form" method="POST" action="{{ route('staff.drives.slots.generate', $drive) }}" class="space-y-4" x-on:submit="handleGeneratorSubmit">
                    @csrf

                    {{-- Dates selection --}}
                    <div>
                        <label class="block text-xs font-semibold text-neutral-800 uppercase tracking-wider mb-1.5">
                            Select Drive Date(s)
                        </label>
                        @php
                            $start = \Carbon\CarbonImmutable::parse($drive->starts_on);
                            $end = \Carbon\CarbonImmutable::parse($drive->ends_on);
                            $driveDates = [];
                            for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                                $driveDates[] = $date->format('Y-m-d');
                            }
                        @endphp
                        <div class="flex flex-wrap gap-2">
                            @foreach ($driveDates as $dDate)
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-300 bg-neutral-50/50 hover:bg-white text-xs font-medium cursor-pointer">
                                    <input type="checkbox" name="dates[]" value="{{ $dDate }}" class="rounded text-primary-600 focus:ring-primary-500" checked>
                                    <span>{{ \Carbon\CarbonImmutable::parse($dDate)->format('D, d M') }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Hours and Length --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Start Time (EAT)</label>
                            <input type="time" name="start_time" x-model="generator.startTime" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">End Time (EAT)</label>
                            <input type="time" name="end_time" x-model="generator.endTime" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Slot Length</label>
                            <select name="slot_length" x-model="generator.slotLength" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                                <option value="10">10 minutes</option>
                                <option value="15">15 minutes</option>
                                <option value="20">20 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                    </div>

                    {{-- Capacity & Break Window --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Capacity / Slot</label>
                            <input type="number" name="capacity" x-model="generator.capacity" min="1" max="100" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Break Start (Optional)</label>
                            <input type="time" name="break_start_time" x-model="generator.breakStart" placeholder="e.g. 13:00" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Break End (Optional)</label>
                            <input type="time" name="break_end_time" x-model="generator.breakEnd" placeholder="e.g. 14:00" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none">
                        </div>
                    </div>

                    {{-- Preview Trigger Button --}}
                    <div class="pt-2 flex justify-between items-center">
                        <button
                            type="button"
                            x-on:click="runPreview"
                            :disabled="loadingPreview"
                            class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-neutral-900 text-white hover:bg-neutral-800 disabled:opacity-50 transition-colors"
                        >
                            <span x-show="!loadingPreview">Generate Preview</span>
                            <span x-show="loadingPreview" x-cloak>Calculating preview...</span>
                        </button>
                        <span class="text-[11px] text-neutral-400">Max 200 slots per run</span>
                    </div>

                    {{-- Preview Results Box --}}
                    <template x-if="previewData">
                        <div class="mt-4 p-4 rounded-xl bg-neutral-50 border border-neutral-200 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-bold text-neutral-900">
                                    Preview: <span class="text-primary-700" x-text="previewData.total_to_create"></span> slots to be created
                                </div>
                                <div class="text-xs text-neutral-500">
                                    Skipped: <strong x-text="previewData.total_skipped"></strong>
                                    (<span x-text="previewData.skipped_collisions"></span> collisions,
                                    <span x-text="previewData.skipped_breaks"></span> break window,
                                    <span x-text="previewData.skipped_past"></span> past)
                                </div>
                            </div>

                            {{-- Scrollable List of Generated Times --}}
                            <div class="max-h-48 overflow-y-auto border border-neutral-200 rounded-lg bg-white p-2 text-xs divide-y divide-neutral-100">
                                <template x-for="(slot, idx) in previewData.slots" :key="idx">
                                    <div class="py-1.5 px-2 flex justify-between items-center text-neutral-700">
                                        <span class="font-medium" x-text="slot.formatted_date + ' — ' + slot.display_range"></span>
                                        <span class="text-neutral-500" x-text="'Capacity: ' + slot.capacity"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Error Alert --}}
                    <template x-if="previewError">
                        <div class="p-3 bg-danger-50 border border-danger-200 rounded-lg text-xs text-danger-700 font-medium" x-text="previewError"></div>
                    </template>

                    <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-2">
                        <x-button type="button" variant="secondary" size="sm" x-on:click="show = false; $dispatch('close-modal', 'generator-modal')">
                            Cancel
                        </x-button>
                        <button
                            type="submit"
                            :disabled="!previewData || previewData.total_to_create === 0"
                            class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            Confirm & Save Slots
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>

    @push('scripts')
    <script>
        function driveManagement() {
            return {
                generatorExpanded: false,
                editSlot: {
                    id: null,
                    startTime: '',
                    endTime: '',
                    capacity: 10,
                    bookedCount: 0,
                    date: ''
                },
                deleteSlot: {
                    id: null,
                    range: ''
                },
                generator: {
                    startTime: '09:00',
                    endTime: '17:00',
                    slotLength: '30',
                    capacity: '10',
                    breakStart: '13:00',
                    breakEnd: '14:00'
                },
                loadingPreview: false,
                previewData: null,
                previewError: null,

                openEditSlotModal(id, startTime, endTime, capacity, bookedCount, date) {
                    this.editSlot = { id, startTime, endTime, capacity, bookedCount, date };
                    this.$dispatch('open-modal', 'edit-slot-modal');
                },

                openDeleteSlotModal(id, range) {
                    this.deleteSlot = { id, range };
                    this.$dispatch('open-modal', 'delete-slot-modal');
                },

                async runPreview(formId = 'generator-form') {
                    this.loadingPreview = true;
                    this.previewError = null;

                    const form = document.getElementById(formId) || document.getElementById('generator-form');
                    const formData = new FormData(form);

                    try {
                        const response = await fetch('{{ route('staff.drives.slots.preview', $drive) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            if (data.errors) {
                                const msg = Object.values(data.errors).flat().join(' ');
                                this.previewError = msg;
                            } else {
                                this.previewError = data.message || 'Failed to calculate preview.';
                            }
                            this.previewData = null;
                        } else {
                            this.previewData = data;
                        }
                    } catch (e) {
                        this.previewError = 'Network error calculating preview.';
                        this.previewData = null;
                    } finally {
                        this.loadingPreview = false;
                    }
                }
            }
        }
    </script>
    @endpush
</x-layouts.staff>
