<x-layouts.public
    title="Upcoming Blood Donation Drives"
    meta-description="Browse upcoming Uganda Blood Transfusion Services (UBTS) donation drives across Kampala and regional centers, and view available time slots."
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-8">
        {{-- Hero Header --}}
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 pb-2">
            <div class="max-w-3xl space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-200/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-600 animate-pulse"></span>
                    Uganda Blood Transfusion Services &bull; Active Schedules
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-neutral-900 font-sans">
                    Upcoming Blood Donation Drives
                </h1>
                <p class="text-sm sm:text-base text-neutral-600 leading-relaxed max-w-2xl">
                    Browse scheduled voluntary blood donation drives across Kampala and regional blood banks. Reserve your preferred time slot online to save lives with zero waiting time.
                </p>
            </div>

            {{-- Quick Stats Banner --}}
            <div class="flex items-center gap-3 bg-white p-3 rounded-2xl border border-neutral-200/80 shadow-xs shrink-0 text-xs">
                <div class="w-9 h-9 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-neutral-900 text-sm block">{{ $drives->total() }}</span>
                    <span class="text-neutral-500 text-[11px]">Published Drives Found</span>
                </div>
            </div>
        </div>

        {{-- Search & Availability Filters Bar --}}
        <div class="bg-white rounded-2xl border border-neutral-200/80 shadow-xs p-4 sm:p-5">
            <form method="GET" action="{{ route('drives.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                {{-- Search Input --}}
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="q"
                        value="{{ $search ?? '' }}"
                        placeholder="Search by venue, city, or landmark (e.g. Nakasero, Ntinda, Mbarara, Jinja)..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-neutral-300 hover:border-neutral-400 focus:border-primary-600 focus:ring-4 focus:ring-primary-500/10 text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none transition-all"
                    />
                </div>

                {{-- Filter Chips & Submit --}}
                <div class="flex items-center gap-2 shrink-0">
                    <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">

                    <div class="inline-flex rounded-xl border border-neutral-200 p-1 bg-neutral-50 text-xs font-semibold">
                        <a
                            href="{{ route('drives.index', ['q' => $search ?? '', 'filter' => 'all']) }}"
                            class="px-3 py-1.5 rounded-lg transition-colors {{ ($filter ?? 'all') === 'all' ? 'bg-white text-primary-700 shadow-2xs font-bold' : 'text-neutral-600 hover:text-neutral-900' }}"
                        >
                            All Drives
                        </a>
                        <a
                            href="{{ route('drives.index', ['q' => $search ?? '', 'filter' => 'available']) }}"
                            class="px-3 py-1.5 rounded-lg transition-colors {{ ($filter ?? 'all') === 'available' ? 'bg-white text-primary-700 shadow-2xs font-bold' : 'text-neutral-600 hover:text-neutral-900' }}"
                        >
                            Slots Available Only
                        </a>
                    </div>

                    <button
                        type="submit"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-primary-600 text-white hover:bg-primary-700 transition-colors shadow-xs cursor-pointer min-h-[42px]"
                    >
                        Filter
                    </button>

                    @if(!empty($search) || ($filter ?? 'all') !== 'all')
                        <a
                            href="{{ route('drives.index') }}"
                            class="px-3 py-2.5 rounded-xl text-xs font-semibold text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                            title="Reset filters"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Drives Grid or Empty State --}}
        @if ($drives->isEmpty())
            <div class="py-16 text-center bg-white rounded-2xl border border-neutral-200/80 shadow-xs p-8 max-w-xl mx-auto space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-neutral-900">No matching blood drives found</h2>
                    <p class="mt-1 text-sm text-neutral-500">
                        @if(!empty($search))
                            No upcoming drives matched "<strong>{{ $search }}</strong>". Try searching another location or clear your query.
                        @else
                            There are currently no active public blood donation drives matching your criteria.
                        @endif
                    </p>
                </div>
                <div>
                    <a
                        href="{{ route('drives.index') }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-primary-600 text-white hover:bg-primary-700 transition-colors shadow-xs"
                    >
                        View All Upcoming Drives
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($drives as $drive)
                    @php
                        $hasUpcomingSlots = ($drive->upcoming_slots_count ?? 0) > 0;
                        $capacity = (int) ($drive->upcoming_capacity ?? 0);
                        $booked = (int) ($drive->upcoming_booked ?? 0);
                        $spotsLeft = max(0, $capacity - $booked);
                        $percentBooked = $capacity > 0 ? min(100, round(($booked / $capacity) * 100)) : 0;
                    @endphp
                    <div class="group bg-white rounded-2xl border border-neutral-200/90 shadow-xs hover:shadow-md hover:border-neutral-300 transition-all duration-200 flex flex-col justify-between overflow-hidden relative">
                        {{-- Top Accent Strip --}}
                        <div class="h-1 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

                        <div class="p-6 space-y-4">
                            {{-- Date Badge & EAT tag --}}
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-primary-800 bg-primary-50 px-2.5 py-1 rounded-md border border-primary-100 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
                                </span>
                                <span class="font-mono text-neutral-400 text-[11px]">EAT</span>
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-neutral-900 group-hover:text-primary-700 transition-colors line-clamp-2 leading-snug">
                                    <a href="{{ route('drives.show', $drive) }}">
                                        {{ $drive->title }}
                                    </a>
                                </h2>
                                <p class="mt-2 text-xs text-neutral-600 flex items-start gap-1.5 font-medium">
                                    <svg class="w-4 h-4 text-neutral-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="line-clamp-1">{{ $drive->venue }}@if($drive->address) &bull; {{ $drive->address }}@endif</span>
                                </p>
                            </div>

                            @if ($drive->description)
                                <p class="text-xs text-neutral-500 line-clamp-2 leading-relaxed">
                                    {{ $drive->description }}
                                </p>
                            @endif

                            {{-- Capacity / Progress Bar --}}
                            @if($hasUpcomingSlots && $capacity > 0)
                                <div class="pt-2 space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] text-neutral-500">
                                        <span>Capacity Booking</span>
                                        <span class="font-semibold text-neutral-800">{{ $spotsLeft }} of {{ $capacity }} spots available</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-neutral-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-300 {{ $percentBooked >= 90 ? 'bg-danger-500' : ($percentBooked >= 60 ? 'bg-amber-500' : 'bg-primary-600') }}"
                                            style="width: {{ $percentBooked }}%"
                                        ></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Footer with Spots Left and Link --}}
                        <div class="px-6 py-4 bg-neutral-50/70 border-t border-neutral-100 flex items-center justify-between gap-3 text-xs">
                            <div>
                                @if (!$hasUpcomingSlots)
                                    <span class="inline-flex items-center gap-1 font-medium text-neutral-500">
                                        <span class="w-2 h-2 rounded-full bg-neutral-300"></span>
                                        No slots available
                                    </span>
                                @elseif ($spotsLeft === 0)
                                    <span class="inline-flex items-center gap-1 font-semibold text-danger-700 bg-danger-50 px-2 py-0.5 rounded">
                                        <span class="w-1.5 h-1.5 rounded-full bg-danger-500"></span>
                                        Fully booked
                                    </span>
                                @elseif ($spotsLeft <= 5)
                                    <span class="inline-flex items-center gap-1 font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Only {{ $spotsLeft }} {{ Str::plural('spot', $spotsLeft) }} left
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $spotsLeft }} spots open
                                    </span>
                                @endif
                            </div>

                            <a
                                href="{{ route('drives.show', $drive) }}"
                                class="inline-flex items-center justify-center px-3.5 py-1.5 rounded-lg text-xs font-bold bg-neutral-900 text-white hover:bg-neutral-800 group-hover:bg-primary-600 transition-colors shadow-2xs"
                                aria-label="Book or view schedule for {{ $drive->title }}"
                            >
                                Book Slot &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination Links --}}
            <div class="pt-6">
                {{ $drives->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
