<x-layouts.public
    title="Upcoming Blood Donation Drives"
    meta-description="Browse upcoming Uganda Blood Transfusion Services (UBTS) donation drives across Kampala and regional centers, and view available time slots."
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-10">
        {{-- Hero Header --}}
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-200/60 mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-600 animate-pulse"></span>
                Official UBTS Donation Schedules
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-neutral-900">
                Upcoming Blood Drives
            </h1>
            <p class="mt-3 text-base sm:text-lg text-neutral-600 leading-relaxed">
                Browse scheduled voluntary blood donation drives across Kampala and regional blood collection centres. Check venue details and time slot availability.
            </p>
        </div>

        {{-- Drives Grid or Empty State --}}
        @if ($drives->isEmpty())
            <div class="py-16 text-center bg-white rounded-2xl border border-neutral-200/80 shadow-xs p-8 max-w-xl mx-auto">
                <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-neutral-900">No scheduled drives</h2>
                <p class="mt-2 text-sm text-neutral-600">
                    There are no drives scheduled right now. Please check back soon.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($drives as $drive)
                    @php
                        $hasUpcomingSlots = ($drive->upcoming_slots_count ?? 0) > 0;
                        $spotsLeft = max(0, (int) ($drive->upcoming_capacity ?? 0) - (int) ($drive->upcoming_booked ?? 0));
                    @endphp
                    <div class="group bg-white rounded-2xl border border-neutral-200/80 shadow-xs hover:shadow-md hover:border-neutral-300 transition-all duration-200 flex flex-col justify-between overflow-hidden">
                        <div class="p-6 space-y-4">
                            {{-- Date Badge & Venue --}}
                            <div class="flex items-center justify-between text-xs text-neutral-500">
                                <span class="font-semibold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-md">
                                    {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
                                </span>
                                <span>EAT</span>
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-neutral-900 group-hover:text-primary-700 transition-colors line-clamp-2">
                                    <a href="{{ route('drives.show', $drive) }}">
                                        {{ $drive->title }}
                                    </a>
                                </h2>
                                <p class="mt-1.5 text-xs text-neutral-600 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="truncate font-medium">{{ $drive->venue }}</span>
                                </p>
                            </div>

                            @if ($drive->description)
                                <p class="text-xs text-neutral-500 line-clamp-2">
                                    {{ $drive->description }}
                                </p>
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
                                    <span class="inline-flex items-center gap-1 font-semibold text-danger-600">
                                        <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                                        Fully booked
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-semibold text-success-700">
                                        <span class="w-2 h-2 rounded-full bg-success-500"></span>
                                        {{ $spotsLeft }} spots left
                                    </span>
                                @endif
                            </div>

                            <a
                                href="{{ route('drives.show', $drive) }}"
                                class="font-semibold text-primary-700 hover:text-primary-900 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform"
                                aria-label="View details for {{ $drive->title }}"
                            >
                                View details &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $drives->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
