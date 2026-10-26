<x-layouts.staff title="System Telemetry & Health — Admin Portal">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-primary-100 text-primary-800 border border-primary-200">
                        Admin Only
                    </span>
                    <span class="text-xs text-neutral-500">Operational Observability</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900 font-display">System Telemetry &amp; Background Workers</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    Real-time status of the scheduler heartbeat, queue workers, send budgets, and failed jobs.
                </p>
            </div>
        </div>

        {{-- Background Warning Banner if scheduler or queue worker is dead --}}
        @if ($telemetry['has_issue'])
            <div class="p-4 rounded-xl bg-danger-50 border border-danger-200 text-danger-900 flex items-start gap-3">
                <svg class="w-5 h-5 text-danger-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm">
                    <strong class="font-semibold">Reminders may not be sending:</strong>
                    <span>Background scheduler or queue worker heartbeat is missing or stale. Ensure worker processes and scheduled crons are running on Laravel Cloud.</span>
                </div>
            </div>
        @endif

        {{-- Telemetry Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Scheduler Heartbeat --}}
            <x-card padding="md" class="space-y-1">
                <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Scheduler Heartbeat</div>
                <div class="flex items-center gap-2 pt-1">
                    @if ($telemetry['scheduler']['status'] === 'healthy')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-success-100 text-success-800">
                            Healthy (&lt; 3m)
                        </span>
                    @elseif ($telemetry['scheduler']['status'] === 'stale')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-danger-100 text-danger-800">
                            Stale ({{ $telemetry['scheduler']['age_seconds'] }}s ago)
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-200 text-neutral-800">
                            Never Run
                        </span>
                    @endif
                </div>
                <p class="text-xs text-neutral-500 pt-1">
                    Threshold: 180 seconds.
                </p>
            </x-card>

            {{-- Queue Worker Heartbeat --}}
            <x-card padding="md" class="space-y-1">
                <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Queue Worker Heartbeat</div>
                <div class="flex items-center gap-2 pt-1">
                    @if ($telemetry['queue_worker']['status'] === 'healthy')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-success-100 text-success-800">
                            Healthy (&lt; 15m)
                        </span>
                    @elseif ($telemetry['queue_worker']['status'] === 'stale')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-danger-100 text-danger-800">
                            Stale ({{ $telemetry['queue_worker']['age_seconds'] }}s ago)
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-neutral-200 text-neutral-800">
                            Never Run
                        </span>
                    @endif
                </div>
                <p class="text-xs text-neutral-500 pt-1">
                    Pending Jobs: <strong class="text-neutral-800">{{ $telemetry['pending_jobs_count'] }}</strong>
                </p>
            </x-card>

            {{-- Mail Budget (Last 24 Hours) --}}
            <x-card padding="md" class="space-y-1">
                <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500">24h Email Volume</div>
                <div class="text-xl font-bold text-neutral-900 pt-1">
                    {{ $telemetry['emails_sent_24h'] }} <span class="text-sm font-normal text-neutral-500">/ {{ $telemetry['daily_budget'] }} budget</span>
                </div>
                <p class="text-xs text-neutral-500 pt-1">
                    Failed sends (24h): <strong class="{{ $telemetry['failed_notifications_24h'] > 0 ? 'text-danger-600' : 'text-neutral-700' }}">{{ $telemetry['failed_notifications_24h'] }}</strong>
                </p>
            </x-card>

            {{-- Environment & Engine --}}
            <x-card padding="md" class="space-y-1">
                <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Database &amp; Engine</div>
                <div class="text-sm font-bold text-neutral-900 pt-1 capitalize">
                    {{ $telemetry['database_driver'] }}
                </div>
                <p class="text-xs text-neutral-500 pt-1">
                    App v{{ $telemetry['app_version'] }} &bull; PHP {{ $telemetry['php_version'] }}
                </p>
            </x-card>
        </div>

        {{-- Background Sweep Timestamps --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-card padding="md">
                <h2 class="text-sm font-bold text-neutral-900 mb-1">Last Appointment Reminder Sweep</h2>
                <p class="text-xs text-neutral-600">
                    @if ($telemetry['last_reminder_sweep'])
                        {{ $telemetry['last_reminder_sweep']->setTimezone('Africa/Kampala')->format('M d, Y H:i:s') }} EAT
                        ({{ $telemetry['last_reminder_sweep']->diffForHumans() }})
                    @else
                        <span class="text-neutral-400">No reminder sweeps recorded yet</span>
                    @endif
                </p>
            </x-card>

            <x-card padding="md">
                <h2 class="text-sm font-bold text-neutral-900 mb-1">Last Drive Announcement Run</h2>
                <p class="text-xs text-neutral-600">
                    @if ($telemetry['last_announcement_run'])
                        {{ $telemetry['last_announcement_run']->setTimezone('Africa/Kampala')->format('M d, Y H:i:s') }} EAT
                        ({{ $telemetry['last_announcement_run']->diffForHumans() }})
                    @else
                        <span class="text-neutral-400">No announcement runs recorded yet</span>
                    @endif
                </p>
            </x-card>
        </div>

        {{-- Failed Jobs Table (Last 20) --}}
        <x-card padding="none">
            <div class="p-4 border-b border-neutral-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-neutral-900 font-display">Recent Failed Queue Jobs</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Displaying up to 20 most recent failed jobs (payloads and sensitive arguments omitted).
                    </p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 bg-neutral-100 text-neutral-700 rounded-md">
                    Total failed: {{ $telemetry['failed_jobs_count'] }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50 border-b border-neutral-200 text-neutral-700 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Job Class</th>
                            <th class="px-4 py-3">Failed At</th>
                            <th class="px-4 py-3">Exception Summary</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($telemetry['failed_jobs'] as $fJob)
                            <tr class="hover:bg-neutral-50/50">
                                <td class="px-4 py-3 font-mono text-[11px] text-neutral-500">
                                    #{{ $fJob['id'] }}
                                </td>
                                <td class="px-4 py-3 font-medium text-neutral-900 font-mono text-[11px]">
                                    {{ $fJob['job'] }}
                                </td>
                                <td class="px-4 py-3 text-neutral-600 whitespace-nowrap">
                                    {{ $fJob['failed_at'] }}
                                </td>
                                <td class="px-4 py-3 text-danger-700 font-mono text-[11px]">
                                    {{ $fJob['exception'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-neutral-500">
                                    No failed queue jobs recorded. Queue worker is running cleanly.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-layouts.staff>
