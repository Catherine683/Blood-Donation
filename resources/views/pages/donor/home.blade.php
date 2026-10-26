<x-layouts.donor title="Home — Donor Portal">
    <x-page-header
        title="Welcome back, {{ auth()->user()->name }}"
        description="Uganda Blood Transfusion Services (UBTS) Voluntary Donor Portal"
    >
        <x-slot:badge>
            <x-badge variant="success" size="md">
                Active Donor
            </x-badge>
        </x-slot:badge>
    </x-page-header>

    {{-- Overview Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Next Appointment Card --}}
        <x-card title="Your Next Appointment" padding="md">
            @if ($nextAppointment)
                @php
                    $nextSlot = $nextAppointment->slot;
                    $nextDrive = $nextSlot?->drive;
                @endphp
                <div class="py-2 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-0.5 rounded">
                            {{ \App\Support\DateHelper::formatDate($nextSlot?->starts_at) }}
                        </span>
                        <x-badge :variant="$nextAppointment->status->badgeVariant()" size="sm">
                            {{ $nextAppointment->status->label() }}
                        </x-badge>
                    </div>
                    <h3 class="text-sm font-bold text-neutral-900 leading-snug line-clamp-1">
                        {{ $nextDrive?->title ?? 'Donation Session' }}
                    </h3>
                    <p class="text-xs font-medium text-neutral-700">
                        {{ \App\Support\DateHelper::formatRange($nextSlot?->starts_at, $nextSlot?->ends_at) }}
                    </p>
                    <p class="text-xs text-neutral-500 truncate">
                        {{ $nextDrive?->venue }}
                    </p>
                </div>
                <x-slot:footer>
                    <x-button :href="route('donor.appointments')" variant="ghost" size="sm" class="w-full text-xs font-semibold text-primary-700">
                        Manage Appointment &rarr;
                    </x-button>
                </x-slot:footer>
            @else
                <div class="py-4 text-center">
                    <div class="w-10 h-10 rounded-full bg-neutral-100 text-neutral-500 mx-auto mb-3 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-neutral-800">No scheduled appointment</p>
                    <p class="text-xs text-neutral-500 mt-1 max-w-xs mx-auto">
                        Reserve a time slot at an upcoming blood drive to schedule your next donation.
                    </p>
                </div>
                <x-slot:footer>
                    <x-button :href="route('drives.index')" variant="ghost" size="sm" class="w-full text-xs font-semibold text-primary-700">
                        Browse Drives &rarr;
                    </x-button>
                </x-slot:footer>
            @endif
        </x-card>

        {{-- Donation History Card --}}
        <x-card title="Donation History" padding="md">
            <div class="py-4 text-center">
                <div class="text-3xl font-bold text-neutral-900 mb-1">0</div>
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Recorded Donations</p>
                <p class="text-xs text-neutral-500 mt-2 max-w-xs mx-auto">
                    Every donation confirmed by UBTS on site is recorded here.
                </p>
            </div>
            <x-slot:footer>
                <x-button :href="route('donor.history')" variant="ghost" size="sm" class="w-full text-xs">
                    View Full History &rarr;
                </x-button>
            </x-slot:footer>
        </x-card>

        {{-- Profile Summary Card --}}
        <x-card title="Donor Account" padding="md">
            <dl class="space-y-2.5 text-xs">
                <div class="flex justify-between py-1 border-b border-neutral-100">
                    <dt class="text-neutral-500 font-medium">Email:</dt>
                    <dd class="text-neutral-900 font-medium truncate max-w-[150px]">{{ auth()->user()->email }}</dd>
                </div>
                <div class="flex justify-between py-1 border-b border-neutral-100">
                    <dt class="text-neutral-500 font-medium">Status:</dt>
                    <dd class="text-success-700 font-semibold">Email Verified</dd>
                </div>
                <div class="flex justify-between py-1">
                    <dt class="text-neutral-500 font-medium">Role:</dt>
                    <dd class="text-neutral-900 font-medium capitalize">{{ auth()->user()->role->label() }}</dd>
                </div>
            </dl>
            <x-slot:footer>
                <x-button :href="route('donor.profile')" variant="ghost" size="sm" class="w-full text-xs">
                    Manage Profile &rarr;
                </x-button>
            </x-slot:footer>
        </x-card>
    </div>

    {{-- Upcoming Drives Section --}}
    <div class="mb-8 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-neutral-900">Upcoming Blood Drives</h2>
                <p class="text-xs text-neutral-500">Scheduled community donation sessions open for donors.</p>
            </div>
            <a href="{{ route('drives.index') }}" class="text-xs font-semibold text-primary-700 hover:text-primary-900 transition-colors">
                View all drives &rarr;
            </a>
        </div>

        @if (!isset($upcomingDrives) || $upcomingDrives->isEmpty())
            <x-card padding="md">
                <div class="py-6 text-center">
                    <p class="text-sm font-medium text-neutral-700">No scheduled drives right now</p>
                    <p class="text-xs text-neutral-500 mt-1">Please check back soon for upcoming donation opportunities.</p>
                </div>
            </x-card>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($upcomingDrives as $drive)
                    @php
                        $hasUpcomingSlots = ($drive->upcoming_slots_count ?? 0) > 0;
                        $spotsLeft = max(0, (int) ($drive->upcoming_capacity ?? 0) - (int) ($drive->upcoming_booked ?? 0));
                    @endphp
                    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-xs p-4 flex flex-col justify-between hover:shadow-sm hover:border-neutral-300 transition-all">
                        <div class="space-y-2">
                            <span class="inline-block text-[11px] font-semibold text-primary-700 bg-primary-50 px-2 py-0.5 rounded">
                                {{ \App\Support\DateHelper::formatDateRange($drive->starts_on, $drive->ends_on) }}
                            </span>
                            <h3 class="font-bold text-sm text-neutral-900 line-clamp-1">
                                <a href="{{ route('drives.show', $drive) }}" class="hover:text-primary-700">
                                    {{ $drive->title }}
                                </a>
                            </h3>
                            <p class="text-xs text-neutral-500 truncate flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $drive->venue }}
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-t border-neutral-100 flex items-center justify-between text-xs">
                            <div>
                                @if (!$hasUpcomingSlots)
                                    <span class="text-neutral-500 font-medium">No slots</span>
                                @elseif ($spotsLeft === 0)
                                    <span class="text-danger-600 font-semibold">Fully booked</span>
                                @else
                                    <span class="text-success-700 font-semibold">{{ $spotsLeft }} spots</span>
                                @endif
                            </div>
                            <a href="{{ route('drives.show', $drive) }}" class="font-semibold text-primary-700 hover:text-primary-900">
                                View details &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Information Note --}}
    <x-alert variant="info" title="Booking in Phase 3">
        Online slot booking will be introduced in Phase 3. You can browse published drives and check slot schedules in the meantime.
    </x-alert>
</x-layouts.donor>
