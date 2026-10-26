<x-layouts.staff title="Drives — Staff Portal">
    <div class="space-y-6" x-data="{ driveToDelete: null, driveToDeleteTitle: '' }">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Blood Donation Drives</h1>
                <p class="text-sm text-neutral-600 mt-1">
                    Manage venues, configure time slot capacities, and publish donation drives for public booking.
                </p>
            </div>
            <div>
                <x-button :href="route('staff.drives.create')" variant="primary" size="md" class="w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create a drive
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

        {{-- Filter & Search Bar --}}
        <x-card padding="md">
            <form method="GET" action="{{ route('staff.drives') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                {{-- Search --}}
                <div>
                    <label for="filter-search" class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1.5">
                        Search
                    </label>
                    <input
                        type="text"
                        name="search"
                        id="filter-search"
                        value="{{ request('search') }}"
                        placeholder="Search title or venue..."
                        class="w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                    >
                </div>

                {{-- Status Filter --}}
                <div>
                    <label for="filter-status" class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1.5">
                        Status
                    </label>
                    <select
                        name="status"
                        id="filter-status"
                        class="w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                    >
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>All statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                {{-- Timeline Filter --}}
                <div>
                    <label for="filter-timeline" class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1.5">
                        Timeline
                    </label>
                    <select
                        name="timeline"
                        id="filter-timeline"
                        class="w-full rounded-lg border border-neutral-300 bg-white px-3.5 py-2 text-sm text-neutral-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20"
                    >
                        <option value="all" {{ request('timeline') === 'all' || !request('timeline') ? 'selected' : '' }}>All dates</option>
                        <option value="upcoming" {{ request('timeline') === 'upcoming' ? 'selected' : '' }}>Upcoming & Active</option>
                        <option value="past" {{ request('timeline') === 'past' ? 'selected' : '' }}>Past drives</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-neutral-900 text-white hover:bg-neutral-800 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900"
                    >
                        Apply Filters
                    </button>
                    @if (request()->hasAny(['search', 'status', 'timeline']) && (request('search') || request('status') !== 'all' || request('timeline') !== 'all'))
                        <a
                            href="{{ route('staff.drives') }}"
                            class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                            title="Reset filters"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Drives Content --}}
        @if ($drives->isEmpty())
            <x-empty-state
                title="No donation drives found"
                description="{{ request()->hasAny(['search', 'status', 'timeline']) ? 'No drives match your current filter criteria. Try resetting the filters or create a new drive.' : 'No blood drives have been created yet. Create the first drive to start scheduling donor slots.' }}"
            >
                <x-slot:icon>
                    <svg class="w-6 h-6 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </x-slot:icon>
                <x-button :href="route('staff.drives.create')" variant="primary" size="md">
                    Create a drive
                </x-button>
            </x-empty-state>
        @else
            {{-- Desktop Table View (Hidden on mobile) --}}
            <div class="hidden md:block bg-white rounded-xl shadow-xs border border-neutral-200 overflow-hidden">
                <table class="min-w-full divide-y divide-neutral-200 text-left text-sm">
                    <thead class="bg-neutral-50/75 text-xs font-semibold text-neutral-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3.5 pl-6 pr-3">Drive & Venue</th>
                            <th scope="col" class="px-3 py-3.5">Dates (EAT)</th>
                            <th scope="col" class="px-3 py-3.5">Status</th>
                            <th scope="col" class="px-3 py-3.5 text-right">Slots</th>
                            <th scope="col" class="px-3 py-3.5 text-right">Capacity</th>
                            <th scope="col" class="px-3 py-3.5 text-right">Booked</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white">
                        @foreach ($drives as $drive)
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="py-4 pl-6 pr-3 max-w-xs">
                                    <div class="font-semibold text-neutral-900 truncate">
                                        <a href="{{ route('staff.drives.show', $drive) }}" class="hover:text-primary-600 hover:underline">
                                            {{ $drive->title }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-neutral-500 truncate mt-0.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $drive->venue }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-xs text-neutral-600">
                                    <span class="font-medium text-neutral-900">
                                        {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
                                    </span>
                                    @if ($drive->isPast())
                                        <span class="block text-[11px] text-neutral-400">Past drive</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4">
                                    <x-badge :variant="$drive->status->badgeVariant()" size="sm">
                                        {{ $drive->status->label() }}
                                    </x-badge>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-xs font-medium text-neutral-700">
                                    {{ $drive->slots_count }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-xs font-medium text-neutral-700">
                                    {{ $drive->total_capacity ?? 0 }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-xs font-medium text-neutral-700">
                                    {{ $drive->total_booked ?? 0 }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-6 text-right text-xs font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a
                                            href="{{ route('staff.drives.show', $drive) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-neutral-700 hover:text-neutral-900 hover:bg-neutral-100 font-semibold transition-colors"
                                            title="View drive overview & slots"
                                        >
                                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </a>

                                        @if (!$drive->isPast())
                                            <a
                                                href="{{ route('staff.drives.edit', $drive) }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-primary-700 hover:text-primary-900 hover:bg-primary-50 font-semibold transition-colors"
                                                title="Edit drive details"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit
                                            </a>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-neutral-400 cursor-not-allowed text-[11px]" title="Past drives cannot be edited">
                                                Locked
                                            </span>
                                        @endif

                                        @if ($drive->isDraft())
                                            <button
                                                type="button"
                                                x-on:click="driveToDelete = {{ $drive->id }}; driveToDeleteTitle = '{{ addslashes($drive->title) }}'; $dispatch('open-modal', 'delete-drive-confirm')"
                                                class="inline-flex items-center p-1.5 rounded-lg text-danger-600 hover:text-danger-800 hover:bg-danger-50 transition-colors cursor-pointer"
                                                title="Delete draft drive"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Stacked Cards (Visible only on mobile < 768px) --}}
            <div class="block md:hidden space-y-4">
                @foreach ($drives as $drive)
                    <div class="bg-white rounded-xl shadow-xs border border-neutral-200 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-semibold text-base text-neutral-900 leading-snug">
                                    <a href="{{ route('staff.drives.show', $drive) }}" class="hover:text-primary-600">
                                        {{ $drive->title }}
                                    </a>
                                </h3>
                                <p class="text-xs text-neutral-500 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ $drive->venue }}
                                </p>
                            </div>
                            <x-badge :variant="$drive->status->badgeVariant()" size="sm">
                                {{ $drive->status->label() }}
                            </x-badge>
                        </div>

                        <div class="text-xs text-neutral-600 bg-neutral-50 rounded-lg p-2.5 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Dates:</span>
                                <span class="font-medium text-neutral-800">
                                    {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Slots / Capacity:</span>
                                <span class="font-medium text-neutral-800">
                                    {{ $drive->slots_count }} slots &bull; {{ $drive->total_capacity ?? 0 }} spots
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Booked:</span>
                                <span class="font-medium text-neutral-800">{{ $drive->total_booked ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="pt-1 flex items-center gap-2">
                            <x-button :href="route('staff.drives.show', $drive)" variant="secondary" size="sm" class="flex-1 justify-center">
                                View
                            </x-button>
                            @if (!$drive->isPast())
                                <x-button :href="route('staff.drives.edit', $drive)" variant="secondary" size="sm" class="flex-1 justify-center text-primary-700">
                                    Edit
                                </x-button>
                            @endif
                            @if ($drive->isDraft())
                                <button
                                    type="button"
                                    x-on:click="driveToDelete = {{ $drive->id }}; driveToDeleteTitle = '{{ addslashes($drive->title) }}'; $dispatch('open-modal', 'delete-drive-confirm')"
                                    class="p-2 rounded-lg text-danger-600 hover:text-danger-800 hover:bg-danger-50 border border-neutral-200 transition-colors"
                                    title="Delete draft drive"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $drives->links() }}
            </div>
        @endif

        {{-- Delete Confirmation Modal for Index --}}
        <x-modal name="delete-drive-confirm" title="Delete Draft Drive" maxWidth="sm">
            <div class="space-y-3">
                <p class="text-sm text-neutral-600">
                    Are you sure you want to delete <strong class="text-neutral-900" x-text="driveToDeleteTitle"></strong>?
                </p>
                <p class="text-xs text-danger-600">
                    This action is permanent and will delete the draft drive along with any configured time slots.
                </p>
            </div>
            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'delete-drive-confirm')">
                    Cancel
                </x-button>
                <form :action="'/staff/drives/' + driveToDelete" method="POST">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-danger-600 text-white hover:bg-danger-700 transition-colors cursor-pointer"
                    >
                        Delete Drive
                    </button>
                </form>
            </x-slot:footer>
        </x-modal>
    </div>
</x-layouts.staff>
