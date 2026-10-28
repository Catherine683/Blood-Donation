<x-layouts.donor title="Donation History — Donor Portal">
    <div class="space-y-6 sm:space-y-8">
        {{-- System Header Banner --}}
        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-primary-700 via-primary-600 to-rose-400"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-primary-50 text-primary-800 border border-primary-200/60">
                            <span class="w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
                            UBTS &bull; Official Clinical Donation Records
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 font-mono">
                            #UBTS-D{{ str_pad((string)$user->id, 5, '0', STR_PAD_LEFT) }}
                        </span>
                        @if ($donorProfile?->blood_group)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-primary-600 text-white shadow-2xs font-mono">
                                Blood Group: {{ $donorProfile->blood_group->label() }}
                            </span>
                        @endif
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 font-sans">
                        Donation History &amp; Clinical Records
                    </h1>

                    <p class="text-sm text-neutral-500 max-w-2xl leading-relaxed">
                        Verified audit trail of voluntary blood donations conducted under Uganda Blood Transfusion Services (UBTS) clinical supervision across Uganda.
                    </p>
                </div>

                {{-- Action Shortcuts --}}
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-bold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors min-h-[44px] cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-1.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Records
                    </button>

                    <x-button :href="route('drives.index')" variant="primary" size="md" class="shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Schedule Next Donation
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Clinical Metrics Ribbon --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            {{-- Metric 1: Total Units --}}
            <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-5 relative overflow-hidden group hover:border-neutral-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Verified Units</span>
                    <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-3xl font-extrabold text-neutral-900 tracking-tight">{{ $donationsCount }}</span>
                    <span class="text-xs text-neutral-500 font-medium ml-1">units (450 mL ea)</span>
                </div>
                <div class="mt-2 text-xs text-neutral-500 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Whole blood logged
                </div>
            </div>

            {{-- Metric 2: Total Volume --}}
            <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-5 relative overflow-hidden group hover:border-neutral-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Volume</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1c0 2.21 1.79 4 4 4a1 1 0 011 1v4a6 6 0 11-12 0V8a1 1 0 011-1c2.21 0 4-1.79 4-4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-3xl font-extrabold text-neutral-900 tracking-tight">
                        {{ number_format($totalVolumeMl) }}
                    </span>
                    <span class="text-xs text-neutral-500 font-medium ml-1">mL</span>
                </div>
                <div class="mt-2 text-xs text-neutral-500 flex items-center gap-1">
                    <span>{{ $donationsCount > 0 ? number_format($totalVolumeMl / 1000, 2) . ' Litres collected' : 'No volume recorded' }}</span>
                </div>
            </div>

            {{-- Metric 3: Lives Impacted --}}
            <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-5 relative overflow-hidden group hover:border-neutral-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Lives Saved</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-3xl font-extrabold text-neutral-900 tracking-tight">{{ $livesImpacted }}</span>
                    <span class="text-xs text-neutral-500 font-medium ml-1">potential lives</span>
                </div>
                <div class="mt-2 text-xs text-neutral-500 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    ~3 lives per unit
                </div>
            </div>

            {{-- Metric 4: Last Donation --}}
            <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs p-5 relative overflow-hidden group hover:border-neutral-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Last Donation</span>
                    <div class="w-8 h-8 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-lg sm:text-xl font-extrabold text-neutral-900 tracking-tight block truncate">
                        {{ $lastDonatedOn ?? 'None on file' }}
                    </span>
                </div>
                <div class="mt-2 text-xs text-neutral-500 flex items-center gap-1">
                    @if ($suggestedNextDonationDate)
                        <span class="text-primary-700 font-medium">Eligible ~{{ $suggestedNextDonationDate }}</span>
                    @else
                        <span>Verified on-site by UBTS</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main History Table Card --}}
        <div class="bg-white rounded-2xl border border-neutral-200/90 shadow-xs overflow-hidden">
            <div class="p-5 sm:p-6 bg-white border-b border-neutral-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-neutral-900">
                            Clinical Attendance &amp; Donation Log
                        </h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-neutral-100 text-neutral-700 font-mono">
                            {{ $records->count() }} {{ \Illuminate\Support\Str::plural('Record', $records->count()) }}
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500">
                        Official records confirmed by on-site healthcare professionals and UBTS medical teams.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-neutral-600 bg-neutral-50 border border-neutral-200 px-3 py-1.5 rounded-lg">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Uganda National Blood Bank Sync: Active
                    </span>
                </div>
            </div>

            @if ($records->isEmpty())
                <div class="p-8 sm:p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900 mb-1">No Verified Donation Records Yet</h3>
                    <p class="text-sm text-neutral-500 max-w-md mx-auto mb-6">
                        Once you attend a blood drive or UBTS regional blood bank and medical staff verify your donation, your official records will appear here automatically.
                    </p>
                    <x-button :href="route('drives.index')" variant="primary" size="md" class="shadow-sm">
                        Find a Blood Donation Drive &rarr;
                    </x-button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-neutral-50/80 border-b border-neutral-100 text-[11px] font-bold uppercase tracking-wider text-neutral-500">
                                <th class="py-3 px-4 sm:px-6">Record / Date</th>
                                <th class="py-3 px-4 sm:px-6">Drive / Collection Center</th>
                                <th class="py-3 px-4 sm:px-6">Clinical Outcome</th>
                                <th class="py-3 px-4 sm:px-6">Supervising Agency</th>
                                <th class="py-3 px-4 sm:px-6 text-right">Verification</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 text-xs">
                            @foreach ($records as $record)
                                <tr class="hover:bg-neutral-50/50 transition-colors">
                                    {{-- Record & Date --}}
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                        <div class="font-bold text-neutral-900 text-sm">
                                            {{ \App\Support\DateHelper::formatDate($record->recorded_at) }}
                                        </div>
                                        <div class="text-[11px] font-mono text-neutral-500 mt-0.5">
                                            #UBTS-REC-{{ str_pad((string)$record->id, 5, '0', STR_PAD_LEFT) }}
                                        </div>
                                    </td>

                                    {{-- Drive Venue --}}
                                    <td class="py-4 px-4 sm:px-6">
                                        <div class="font-semibold text-neutral-900">
                                            {{ $record->drive->title }}
                                        </div>
                                        <div class="text-[11px] text-neutral-500 mt-0.5 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            <span class="truncate">{{ $record->drive->venue }}@if($record->drive->address), {{ $record->drive->address }}@endif</span>
                                        </div>
                                    </td>

                                    {{-- Outcome --}}
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                        @if ($record->isDonated())
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1c0 2.21 1.79 4 4 4a1 1 0 011 1v4a6 6 0 11-12 0V8a1 1 0 011-1c2.21 0 4-1.79 4-4a1 1 0 011-1z" clip-rule="evenodd" />
                                                </svg>
                                                Donated (450 mL Whole Blood)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700">
                                                Attended &amp; Screened
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Supervising Agency --}}
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-neutral-600">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-primary-600"></span>
                                            <span class="font-medium text-neutral-800">UBTS Clinical Team</span>
                                        </div>
                                        <div class="text-[11px] text-neutral-400">Voluntary Blood Transfusion</div>
                                    </td>

                                    {{-- Verification Badge --}}
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-right">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50/80 px-2 py-0.5 rounded border border-emerald-100">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Verified On-Site
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Clinical Protocol & Safe Donation Recovery Guidance --}}
        <div class="rounded-2xl border border-neutral-200/90 bg-white p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">
                        Uganda Blood Transfusion Services &bull; Clinical Recovery Standards
                    </h3>
                    <p class="text-xs text-neutral-500">
                        Guidelines compliant with WHO and Ministry of Health Uganda blood transfusion protocols.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 text-xs">
                <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-100 space-y-1.5">
                    <div class="font-bold text-neutral-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-primary-600"></span>
                        Donation Recovery Windows
                    </div>
                    <p class="text-neutral-600 leading-relaxed text-[11px]">
                        UBTS recommends a minimum 90-day interval for male voluntary donors and 120 days for female voluntary donors between whole blood donations to ensure full physiological iron and hemoglobin recovery.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-100 space-y-1.5">
                    <div class="font-bold text-neutral-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        On-Site Health Screening
                    </div>
                    <p class="text-neutral-600 leading-relaxed text-[11px]">
                        Medical eligibility is evaluated strictly on the day of the drive through on-site hemoglobin testing (&ge; 12.5 g/dL), blood pressure measurement, and brief clinical assessment by certified UBTS phlebotomists.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-neutral-50 border border-neutral-100 space-y-1.5">
                    <div class="font-bold text-neutral-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        Post-Donation Care
                    </div>
                    <p class="text-neutral-600 leading-relaxed text-[11px]">
                        Following your session, stay seated in the recovery area for 10-15 minutes, consume light refreshments provided by the team, and hydrate continuously throughout the remainder of the day.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.donor>