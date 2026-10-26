<x-layouts.staff title="Registered Donors — Staff Portal">
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Registered Donors</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    Search voluntary blood donors, inspect attendance history, and manage administrative details.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a
                    href="{{ route('staff.donors.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-xs transition-colors min-h-[44px]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Register Donor</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <a
                        href="{{ route('admin.donations.export') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-neutral-900 text-white hover:bg-neutral-800 shadow-xs transition-colors min-h-[44px]"
                        title="Download all donation records as CSV (Admin Only)"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export CSV</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('status'))
            <x-alert variant="success" :title="session('status')" />
        @endif
        @if (session('error'))
            <x-alert variant="danger" :title="session('error')" />
        @endif

        {{-- Search Card --}}
        <x-card padding="md">
            <form method="GET" action="{{ route('staff.donors') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <div class="w-full relative">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search donors by name, phone number, or email address..."
                        class="w-full text-xs sm:text-sm rounded-lg border border-neutral-300 py-2.5 pl-3 pr-8 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 min-h-[44px]"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto min-h-[44px]">
                        Search
                    </x-button>
                    @if ($search)
                        <x-button :href="route('staff.donors')" variant="secondary" size="md" class="w-full sm:w-auto min-h-[44px]">
                            Clear
                        </x-button>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Donors Table --}}
        <x-card padding="none" class="overflow-hidden">
            @if ($donors->isEmpty())
                <div class="p-8 text-center text-xs text-neutral-500">
                    @if ($search)
                        No donors matched your search query "<strong>{{ $search }}</strong>".
                    @else
                        No registered voluntary blood donors found in the system.
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-neutral-700 divide-y divide-neutral-200">
                        <thead class="bg-neutral-50/80 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Donor Name</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Email Status</th>
                                <th class="px-4 py-3">Last Donated</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 bg-white">
                            @foreach ($donors as $donor)
                                <tr class="hover:bg-neutral-50/50 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-neutral-900">
                                        <a href="{{ route('staff.donors.show', $donor) }}" class="hover:text-primary-700 underline-offset-2 hover:underline">
                                            {{ $donor->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-neutral-600">
                                        {{ $donor->phone ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($donor->hasVerifiedEmail())
                                            <span class="inline-flex items-center gap-1 text-success-700 font-medium">
                                                &check; Verified
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-300">
                                                Unverified
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-neutral-600">
                                        @if ($donor->donorProfile?->last_donated_on)
                                            {{ \App\Support\DateHelper::formatDate($donor->donorProfile->last_donated_on) }}
                                        @else
                                            <span class="text-neutral-400">Never</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a
                                            href="{{ route('staff.donors.show', $donor) }}"
                                            class="inline-flex items-center text-xs font-semibold text-primary-700 hover:text-primary-900 min-h-[44px] px-2"
                                        >
                                            View Profile &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($donors->hasPages())
                    <div class="p-4 border-t border-neutral-100">
                        {{ $donors->links() }}
                    </div>
                @endif
            @endif
        </x-card>
    </div>
</x-layouts.staff>
