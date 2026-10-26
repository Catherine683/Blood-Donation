<x-layouts.staff title="Email Notifications — Admin Portal">
    <div class="space-y-6">
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center text-xs font-medium text-neutral-500 gap-1.5" aria-label="Breadcrumb">
            <span class="text-neutral-500">Administration</span>
            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-neutral-800">Email Notifications</span>
        </nav>

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Email Notification Log</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    System-wide log of queued, sent, skipped, and failed email dispatches with Brevo status tracking.
                </p>
            </div>
        </div>

        {{-- Filters Card --}}
        <x-card padding="md">
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                {{-- Type Filter --}}
                <div>
                    <x-label for="type" value="Notification Type" />
                    <select
                        id="type"
                        name="type"
                        class="w-full rounded-lg border-neutral-300 text-sm shadow-xs focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="all">All Types</option>
                        @foreach ($availableTypes as $val => $label)
                            <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <x-label for="status" value="Delivery Status" />
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border-neutral-300 text-sm shadow-xs focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="all">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Filter --}}
                <div>
                    <x-label for="date" value="Created Date" />
                    <x-input
                        id="date"
                        name="date"
                        type="date"
                        :value="request('date')"
                    />
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="md" class="w-full justify-center">
                        Filter
                    </x-button>
                    @if (request()->hasAny(['type', 'status', 'date']))
                        <x-button :href="route('admin.notifications.index')" variant="ghost" size="md">
                            Reset
                        </x-button>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Table Card --}}
        <x-card padding="none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-left text-sm">
                    <thead class="bg-neutral-50 text-xs font-semibold text-neutral-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Recipient</th>
                            <th scope="col" class="px-6 py-3.5">Notification Type</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5">Sent At (EAT)</th>
                            <th scope="col" class="px-6 py-3.5">Created At (EAT)</th>
                            <th scope="col" class="px-6 py-3.5">Error / Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-neutral-900">
                                        {{ $log->user?->name ?? 'User #' . $log->user_id }}
                                    </div>
                                    <div class="text-xs text-neutral-500">
                                        {{ $log->user?->email ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-xs text-neutral-700 bg-neutral-100 px-2 py-0.5 rounded">
                                        {{ $availableTypes[$log->type] ?? $log->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-badge :variant="$log->status->badgeVariant()" size="sm">
                                        {{ $log->status->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-neutral-600">
                                    @if ($log->sent_at)
                                        {{ \App\Support\DateHelper::formatDate($log->sent_at) }}
                                        <span class="text-neutral-400">&bull;</span>
                                        {{ \App\Support\DateHelper::formatTime($log->sent_at) }}
                                    @else
                                        <span class="text-neutral-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-neutral-500">
                                    {{ \App\Support\DateHelper::formatDate($log->created_at) }}
                                    <span class="text-neutral-400">&bull;</span>
                                    {{ \App\Support\DateHelper::formatTime($log->created_at) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-neutral-600 max-w-xs truncate" title="{{ $log->error }}">
                                    @if ($log->error)
                                        <span class="text-danger-700">{{ $log->error }}</span>
                                    @else
                                        <span class="text-neutral-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-neutral-500">
                                    No notification logs found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200">
                    {{ $logs->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.staff>
