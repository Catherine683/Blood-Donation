<x-layouts.staff title="Compliance Audit Log — Admin Portal">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-warning-100 text-warning-800 border border-warning-200">
                        Admin Only
                    </span>
                    <span class="text-xs text-neutral-500">Uganda Data Protection and Privacy Act, 2019</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Security &amp; Compliance Audit Log</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    Immutable system trail recording privileged donor lookups, outcome recording, and administrative updates.
                </p>
            </div>
        </div>

        {{-- Filters Bar --}}
        <x-card padding="md">
            <form method="GET" action="{{ route('admin.audit-log') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                {{-- Action filter --}}
                <div>
                    <label for="action" class="block text-xs font-semibold text-neutral-700 mb-1">Action</label>
                    <select id="action" name="action" class="w-full text-xs rounded-lg border border-neutral-300 py-2.5 px-3 min-h-[44px]">
                        <option value="">All Actions</option>
                        @foreach ($distinctActions as $act)
                            <option value="{{ $act }}" {{ $actionFilter === $act ? 'selected' : '' }}>
                                {{ $act }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actor filter --}}
                <div>
                    <label for="actor_id" class="block text-xs font-semibold text-neutral-700 mb-1">Actor</label>
                    <select id="actor_id" name="actor_id" class="w-full text-xs rounded-lg border border-neutral-300 py-2.5 px-3 min-h-[44px]">
                        <option value="">All Actors</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" {{ (string) $actorFilter === (string) $actor->id ? 'selected' : '' }}>
                                {{ $actor->name }} ({{ $actor->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label for="date_from" class="block text-xs font-semibold text-neutral-700 mb-1">Date From</label>
                    <input
                        id="date_from"
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="w-full text-xs rounded-lg border border-neutral-300 py-2 px-3 min-h-[44px]"
                    />
                </div>

                {{-- Date To --}}
                <div>
                    <label for="date_to" class="block text-xs font-semibold text-neutral-700 mb-1">Date To</label>
                    <input
                        id="date_to"
                        type="date"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="w-full text-xs rounded-lg border border-neutral-300 py-2 px-3 min-h-[44px]"
                    />
                </div>

                {{-- Filter Actions --}}
                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="md" class="w-full justify-center min-h-[44px]">
                        Filter
                    </x-button>
                    @if ($actionFilter || $actorFilter || $dateFrom || $dateTo)
                        <x-button :href="route('admin.audit-log')" variant="secondary" size="md" class="w-full justify-center min-h-[44px]">
                            Reset
                        </x-button>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Log Table --}}
        <x-card padding="none" class="overflow-hidden">
            @if ($logs->isEmpty())
                <div class="p-8 text-center text-xs text-neutral-400">
                    No audit records match the selected filter criteria.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-neutral-700 divide-y divide-neutral-200">
                        <thead class="bg-neutral-50/80 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Timestamp (EAT)</th>
                                <th class="px-4 py-3">Actor</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Summary</th>
                                <th class="px-4 py-3 text-right">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 bg-white">
                            @foreach ($logs as $log)
                                @php
                                    $actionBadges = [
                                        'donor.viewed' => 'info',
                                        'donor.updated' => 'warning',
                                        'appointment.checked_in' => 'success',
                                        'appointment.check_in_undone' => 'neutral',
                                        'appointment.no_show' => 'neutral',
                                        'appointment.cancelled_by_staff' => 'danger',
                                        'donation.recorded' => 'success',
                                        'donation.corrected' => 'warning',
                                        'drive.closed_out' => 'danger',
                                        'drive.announcement_sent' => 'primary',
                                    ];
                                    $badgeVariant = $actionBadges[$log->action] ?? 'neutral';

                                    // Build human-friendly short summary from metadata
                                    $summaryParts = [];
                                    $meta = $log->metadata ?? [];
                                    if (isset($meta['changed_fields']) && is_array($meta['changed_fields'])) {
                                        $summaryParts[] = 'Changed: ' . implode(', ', $meta['changed_fields']);
                                    }
                                    if (isset($meta['outcome'])) {
                                        $summaryParts[] = 'Outcome: ' . $meta['outcome'];
                                    }
                                    if (isset($meta['no_show_count'])) {
                                        $summaryParts[] = "Marked {$meta['no_show_count']} no-shows";
                                    }
                                    if (isset($meta['has_appointment'])) {
                                        $summaryParts[] = $meta['has_appointment'] ? 'Booked appt' : 'Walk-in';
                                    }
                                    if (isset($meta['recipient_count'])) {
                                        $summaryParts[] = "{$meta['recipient_count']} recipients";
                                    }
                                    if (empty($summaryParts)) {
                                        if (isset($meta['donor_id'])) {
                                            $summaryParts[] = "Donor #{$meta['donor_id']}";
                                        } elseif (isset($meta['appointment_id'])) {
                                            $summaryParts[] = "Appt #{$meta['appointment_id']}";
                                        } elseif (isset($meta['drive_id'])) {
                                            $summaryParts[] = "Drive #{$meta['drive_id']}";
                                        } else {
                                            $summaryParts[] = 'Completed';
                                        }
                                    }
                                    $shortSummary = implode(' • ', $summaryParts);
                                @endphp
                                <tr class="hover:bg-neutral-50/50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-[11px] text-neutral-600">
                                        {{ \App\Support\DateHelper::formatDate($log->created_at, 'Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-neutral-900">
                                        {{ $log->actor?->name ?? 'System' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-badge :variant="$badgeVariant" size="sm">
                                            {{ $log->action }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-neutral-600 font-mono text-[11px]">
                                        @if ($log->subject_type)
                                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-neutral-700">
                                        {{ $shortSummary }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-[11px] text-neutral-500">
                                        {{ $log->ip ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="p-4 border-t border-neutral-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </x-card>
    </div>
</x-layouts.staff>
