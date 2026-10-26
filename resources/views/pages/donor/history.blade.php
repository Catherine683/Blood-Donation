<x-layouts.donor title="Donation History — Donor Portal">
    <div class="space-y-6">
        <x-page-header
            title="Donation History"
            description="A complete record of your voluntary blood donations verified by UBTS staff."
        />

        {{-- Summary Header Card --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-card padding="md" class="border-l-4 border-l-primary-600">
                <span class="block text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                    Total Donations
                </span>
                <span class="text-3xl font-bold text-neutral-900 mt-1 block">
                    {{ $donationsCount }}
                </span>
                <span class="text-xs text-neutral-500 mt-1 block">
                    Voluntary donations recorded at UBTS drives
                </span>
            </x-card>

            <x-card padding="md" class="border-l-4 border-l-primary-600">
                <span class="block text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                    Date of Last Donation
                </span>
                <span class="text-xl sm:text-2xl font-bold text-neutral-900 mt-1 block">
                    {{ $lastDonatedOn ?? 'None recorded' }}
                </span>
                <span class="text-xs text-neutral-500 mt-1 block">
                    Verified by on-site healthcare professionals
                </span>
            </x-card>
        </div>

        {{-- History Records List / Empty State --}}
        @if ($records->isEmpty())
            <x-empty-state
                title="Your donation history will appear here after you attend a drive."
                description="Once you attend an appointment or voluntary walk-in drive and on-site UBTS medical professionals record your visit, your verified records will be listed here."
            >
                <x-slot:icon>
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
                <x-slot:action>
                    <x-button :href="route('drives.index')" variant="primary" size="md">
                        Find a Blood Drive
                    </x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <x-card padding="none" class="overflow-hidden">
                <div class="p-4 bg-neutral-50/80 border-b border-neutral-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-neutral-900">Attendance &amp; Donation Records</h2>
                    <span class="text-xs text-neutral-500">{{ $records->count() }} entries</span>
                </div>

                <div class="divide-y divide-neutral-100">
                    @foreach ($records as $record)
                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-neutral-900">
                                        {{ \App\Support\DateHelper::formatDate($record->recorded_at) }}
                                    </span>
                                    @if ($record->isDonated())
                                        <x-badge variant="success" size="sm">
                                            Donated
                                        </x-badge>
                                    @else
                                        <x-badge variant="neutral" size="sm">
                                            Attended (no donation recorded)
                                        </x-badge>
                                    @endif
                                </div>
                                <h3 class="text-sm font-bold text-neutral-900">
                                    {{ $record->drive->title }}
                                </h3>
                                <p class="text-xs text-neutral-500">
                                    {{ $record->drive->venue }}@if($record->drive->address) &bull; {{ $record->drive->address }}@endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>
</x-layouts.donor>
