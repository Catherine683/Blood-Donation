<x-layouts.staff title="Operations Dashboard — Staff Portal">
    <div class="space-y-6 sm:space-y-8">
        {{-- Header & Period Filter --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-neutral-200">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">Operations Dashboard</h1>
                    <x-badge variant="{{ auth()->user()->isAdmin() ? 'warning' : 'primary' }}" size="sm">
                        {{ auth()->user()->role->label() }}
                    </x-badge>
                </div>
                <p class="text-xs sm:text-sm text-neutral-500 mt-1">
                    Uganda Blood Transfusion Services (UBTS) • Period: {{ $startEat->format('d M Y') }} – {{ $endEat->format('d M Y') }} (EAT)
                </p>
            </div>

            {{-- Controls & Date Range Filter --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <form method="GET" action="{{ route('staff.dashboard') }}" class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-lg border border-neutral-300 p-0.5 bg-neutral-100 text-xs font-medium">
                        <button
                            type="submit"
                            name="preset"
                            value="30d"
                            class="px-2.5 py-1.5 rounded-md transition-colors {{ $preset === '30d' ? 'bg-white font-semibold text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                        >
                            Last 30 days
                        </button>
                        <button
                            type="submit"
                            name="preset"
                            value="90d"
                            class="px-2.5 py-1.5 rounded-md transition-colors {{ $preset === '90d' ? 'bg-white font-semibold text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                        >
                            Last 90 days
                        </button>
                        <button
                            type="submit"
                            name="preset"
                            value="this_year"
                            class="px-2.5 py-1.5 rounded-md transition-colors {{ $preset === 'this_year' ? 'bg-white font-semibold text-neutral-900 shadow-xs' : 'text-neutral-600 hover:text-neutral-900' }}"
                        >
                            This year
                        </button>
                    </div>
                </form>

                {{-- Export Period Summary CSV --}}
                <a
                    href="{{ route('staff.export.period', ['preset' => $preset, 'start_date' => $startEat->toDateString(), 'end_date' => $endEat->toDateString()]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-neutral-900 text-white hover:bg-neutral-800 shadow-xs focus:ring-2 focus:ring-neutral-500 focus:outline-hidden transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Period CSV</span>
                </a>
            </div>
        </div>

        {{-- Warning Banner for Admins when background scheduler or queue worker is dead --}}
        @if (auth()->user()->isAdmin() && app(\App\Services\SystemStatusService::class)->hasBackgroundIssue())
            <div class="p-4 rounded-xl bg-danger-50 border border-danger-200 text-danger-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-danger-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm">
                        <strong class="font-semibold text-danger-800">Reminders may not be sending:</strong>
                        <span>The background scheduler or queue worker heartbeat is missing or stale. Automated appointment reminders and announcements may be stalled.</span>
                    </div>
                </div>
                <a href="{{ route('admin.system') }}" class="text-xs font-semibold text-danger-700 hover:text-danger-900 underline whitespace-nowrap self-start sm:self-center">
                    Check System Telemetry &rarr;
                </a>
            </div>
        @endif

        {{-- 1. Today / Next Drive Panel --}}
        @if($liveOrNext['status'] === 'live')
            @php $liveMetrics = $liveOrNext['metrics']; $liveDrive = $liveOrNext['drive']; @endphp
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 sm:p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                        </span>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Blood Drive Active Today</span>
                            <h2 class="text-lg font-bold text-neutral-900">{{ $liveDrive->title }}</h2>
                            <p class="text-xs text-neutral-600">{{ $liveDrive->venue }} • {{ $liveDrive->starts_on->format('d M') }} to {{ $liveDrive->ends_on->format('d M Y') }}</p>
                        </div>
                    </div>
                    <a
                        href="{{ route('staff.drives.roster', $liveDrive) }}"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition-colors self-start sm:self-auto min-h-[44px]"
                    >
                        <span>Open Live Roster</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                {{-- Live Drive Metrics Strip --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-3 border-t border-emerald-200/70 text-center">
                    <div class="bg-white/80 p-2.5 rounded-lg border border-emerald-100">
                        <span class="text-xs text-neutral-500 font-medium">Expected</span>
                        <p class="text-xl font-bold text-neutral-900 mt-0.5">{{ $liveMetrics['expected'] }}</p>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-lg border border-emerald-100">
                        <span class="text-xs text-neutral-500 font-medium">Checked In</span>
                        <p class="text-xl font-bold text-amber-600 mt-0.5">{{ $liveMetrics['checked_in'] }}</p>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-lg border border-emerald-100">
                        <span class="text-xs text-neutral-500 font-medium">Donated</span>
                        <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ $liveMetrics['donated'] }}</p>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-lg border border-emerald-100">
                        <span class="text-xs text-neutral-500 font-medium">Did Not Donate</span>
                        <p class="text-xl font-bold text-neutral-600 mt-0.5">{{ $liveMetrics['deferred'] }}</p>
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-lg border border-emerald-100 col-span-2 sm:col-span-1">
                        <span class="text-xs text-neutral-500 font-medium">No-Show</span>
                        <p class="text-xl font-bold text-rose-600 mt-0.5">{{ $liveMetrics['no_show'] }}</p>
                    </div>
                </div>
            </div>
        @elseif($liveOrNext['status'] === 'next')
            @php $nextDrive = $liveOrNext['drive']; $nextMetrics = $liveOrNext['metrics']; @endphp
            <div class="rounded-xl border border-neutral-200 bg-neutral-50/70 p-4 sm:p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-primary-700">Next Upcoming Blood Drive</span>
                        </div>
                        <h2 class="text-lg font-bold text-neutral-900 mt-1">{{ $nextDrive->title }}</h2>
                        <p class="text-xs text-neutral-600 mt-0.5">
                            {{ $nextDrive->venue }} • Starts {{ $nextDrive->starts_on->format('D, d M Y') }}
                            <span class="font-semibold text-neutral-900">({{ $liveOrNext['days_to_go'] }} days to go)</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-right sm:text-left">
                            <span class="text-xs text-neutral-500">Bookings / Capacity</span>
                            <p class="text-base font-bold text-neutral-900">
                                {{ $nextMetrics['booked'] }} / {{ $nextMetrics['capacity'] }}
                                <span class="text-xs font-semibold text-primary-600">({{ \App\Services\ReportService::formatRate($nextMetrics['fill_rate']) }})</span>
                            </p>
                        </div>
                        <div class="text-right sm:text-left">
                            <span class="text-xs text-neutral-500">Announcement</span>
                            <p class="text-xs font-semibold {{ $liveOrNext['announcement_sent'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $liveOrNext['announcement_sent'] ? 'Sent' : 'Not sent yet' }}
                            </p>
                        </div>
                        <a
                            href="{{ route('staff.drives.show', $nextDrive) }}"
                            class="inline-flex items-center justify-center px-3.5 py-2 text-xs font-semibold rounded-lg bg-white border border-neutral-300 text-neutral-800 hover:bg-neutral-100 shadow-xs transition-colors min-h-[44px]"
                        >
                            Manage Drive
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. Needs Attention Panel --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h2 class="text-base font-bold text-neutral-900">Needs Attention</h2>
                </div>
                <span class="text-xs text-neutral-500 font-medium">Actionable items requiring staff review</span>
            </div>

            @if(empty($needsAttention))
                <div class="p-6 text-center rounded-lg bg-neutral-50 border border-neutral-100">
                    <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-semibold text-neutral-900">All operations are up to date</p>
                    <p class="text-xs text-neutral-500 mt-0.5">No drives requiring close-out, unrecorded outcomes, or low-fill alerts at this time.</p>
                </div>
            @else
                <div class="divide-y divide-neutral-100">
                    @foreach($needsAttention as $item)
                        <div class="py-3 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <x-badge variant="{{ $item['badge_variant'] }}" size="sm">
                                        {{ ucfirst(str_replace('_', ' ', $item['type'])) }}
                                    </x-badge>
                                    <h3 class="text-sm font-semibold text-neutral-900">{{ $item['title'] }}</h3>
                                </div>
                                <p class="text-xs text-neutral-600">{{ $item['description'] }}</p>
                            </div>
                            <a
                                href="{{ $item['action_url'] }}"
                                class="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 shadow-xs transition-colors self-start sm:self-auto min-h-[44px] sm:min-h-0"
                            >
                                <span>{{ $item['action_label'] }}</span>
                                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 3. Period Summary Cards Grid --}}
        <div>
            <div class="flex items-center justify-between gap-2 mb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-600">Period Summary Metrics</h2>
                <a href="#about-these-numbers" class="text-xs text-primary-600 hover:underline font-medium">View Metric Definitions ↓</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 sm:gap-4">
                {{-- Drives Held --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">Drives Held</span>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $periodMetrics['drives_held'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Ended in period</span>
                </div>

                {{-- Bookings --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">Booked</span>
                    <p class="text-2xl font-bold text-primary-700 mt-1">{{ $periodMetrics['booked'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Live reservations</span>
                </div>

                {{-- Attendance Rate --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">Attendance Rate</span>
                    <p class="text-2xl font-bold text-amber-700 mt-1">
                        {{ \App\Services\ReportService::formatRate($periodMetrics['attendance_rate']) }}
                    </p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Attended ÷ (Att+NoShow)</span>
                </div>

                {{-- Donations Recorded --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">Donated</span>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $periodMetrics['donated'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">
                        Rate: {{ \App\Services\ReportService::formatRate($periodMetrics['donation_rate']) }}
                    </span>
                </div>

                {{-- Walk-ins --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">Walk-Ins</span>
                    <p class="text-2xl font-bold text-purple-700 mt-1">{{ $periodMetrics['walk_ins'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">No prior booking</span>
                </div>

                {{-- First-time Donors --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">First-Time Donors</span>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $periodMetrics['first_time_donors'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">First donation in period</span>
                </div>

                {{-- New Registrations --}}
                <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-neutral-200 shadow-xs col-span-2 sm:col-span-1">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider block">New Registrations</span>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $periodMetrics['new_registrations'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Accounts created</span>
                </div>
            </div>
        </div>

        {{-- 4. SVG Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Turnout by Drive --}}
            <x-charts.turnout-by-drive :data="$periodMetrics['turnout_by_drive']" />

            {{-- Bookings Over Time --}}
            <x-charts.bookings-over-time :data="$periodMetrics['bookings_by_week']" />
        </div>

        {{-- 5. Donor Base Aggregates Strip --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-base font-semibold text-neutral-900">National Voluntary Donor Base</h3>
                    <p class="text-xs text-neutral-500">Aggregate donor community statistics (strictly non-identifying)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <div class="p-4 rounded-lg bg-neutral-50 border border-neutral-100">
                    <span class="text-xs text-neutral-500 font-medium uppercase tracking-wider">Active Donors</span>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $donorBase['active_donors'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Registered accounts</span>
                </div>
                <div class="p-4 rounded-lg bg-neutral-50 border border-neutral-100">
                    <span class="text-xs text-neutral-500 font-medium uppercase tracking-wider">Verified Donors</span>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $donorBase['verified_donors'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Email address confirmed</span>
                </div>
                <div class="p-4 rounded-lg bg-neutral-50 border border-neutral-100">
                    <span class="text-xs text-neutral-500 font-medium uppercase tracking-wider">Announcement Opt-Ins</span>
                    <p class="text-2xl font-bold text-primary-700 mt-1">{{ $donorBase['opted_in_donors'] }}</p>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Consented to new drive notifications</span>
                </div>
            </div>
        </div>

        {{-- 6. "About These Numbers" Reference Panel --}}
        <div id="about-these-numbers" class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 sm:p-6 text-xs text-neutral-700 space-y-4">
            <div class="flex items-center gap-2 text-neutral-900 font-bold text-sm">
                <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>About These Numbers & Metric Definitions</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Booked</p>
                    <p class="mt-1 text-neutral-600">Appointments currently booked, plus those that moved on to attended or no-show (every live booking), excluding cancelled.</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Expected (Live Drive)</p>
                    <p class="mt-1 text-neutral-600">Appointments currently booked or attended whose slots are in the drive, excluding cancelled and no-show.</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Fill Rate</p>
                    <p class="mt-1 text-neutral-600">Booked ÷ total slot capacity. Any rate with a zero denominator displays "No data". Always formatted with one decimal place.</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Attended & Did Not Donate</p>
                    <p class="mt-1 text-neutral-600">Attended: status attended. Donated: outcome donated. Did not donate: outcome where donation was not completed (no clinical details recorded).</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Attendance Rate</p>
                    <p class="mt-1 text-neutral-600">Attended ÷ (attended + no-show), over ended appointments only. Walk-ins are never in this denominator. Displays "No data" if denominator is zero.</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Donation Rate (of recorded outcomes)</p>
                    <p class="mt-1 text-neutral-600">Donated ÷ recorded outcomes (donated + did not donate), including walk-ins. Displays "No data" if no outcomes have been recorded.</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">First-Time Donors & New Registrations</p>
                    <p class="mt-1 text-neutral-600">First-time donors: donors whose first-ever recorded donation fell in this period. New registrations: active voluntary donor accounts created in the period.</p>
                </div>
                <div class="p-3 bg-white rounded-lg border border-neutral-200">
                    <p class="font-bold text-neutral-900">Walk-Ins</p>
                    <p class="mt-1 text-neutral-600">Donation records where no prior appointment was booked. Walk-ins do not occupy slot capacity and are excluded from attendance rate denominator.</p>
                </div>
            </div>
            <p class="text-[11px] text-neutral-500 pt-2 border-t border-neutral-200">
                All date ranges evaluate with East Africa Time (EAT, UTC+3) boundaries. Operational analytics are read-only and never calculate or project donor clinical eligibility.
            </p>
        </div>
    </div>
</x-layouts.staff>
