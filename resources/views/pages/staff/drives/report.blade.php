<x-layouts.staff title="Drive Report: {{ $drive->title }} — Staff Portal">
    {{-- Print Stylesheet --}}
    <style>
        @media print {
            nav, header, footer, .no-print, button, a.btn-action {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 11pt !important;
            }
            .print-only {
                display: block !important;
            }
            .card-box {
                border: 1px solid #D1D5DB !important;
                box-shadow: none !important;
            }
            @page {
                size: A4;
                margin: 1.5cm;
            }
        }
    </style>

    <div class="space-y-6 sm:space-y-8">
        {{-- Navigation & Top Action Bar --}}
        <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-200">
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('staff.drives.show', $drive) }}"
                    class="p-2 rounded-lg text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
                    title="Back to Drive Details"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Drive Performance Report</span>
                        <x-badge variant="{{ $drive->isPublished() ? 'success' : ($drive->isClosed() ? 'neutral' : 'warning') }}" size="sm">
                            {{ $drive->status->label() }}
                        </x-badge>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $drive->title }}</h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 shadow-xs transition-colors"
                >
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print Report</span>
                </button>

                <a
                    href="{{ route('staff.drives.export.summary', $drive) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-neutral-900 text-white hover:bg-neutral-800 shadow-xs transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Summary CSV</span>
                </a>

                <a
                    href="{{ route('staff.drives.export.roster', $drive) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-300 bg-white text-neutral-800 hover:bg-neutral-50 shadow-xs transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Roster CSV</span>
                </a>
            </div>
        </div>

        {{-- Printable Letterhead --}}
        <div class="hidden print-only mb-6 pb-4 border-b-2 border-neutral-800">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900">UGANDA BLOOD TRANSFUSION SERVICES (UBTS)</h2>
                    <p class="text-xs text-neutral-600">Official Mobile Blood Drive Operational Performance Report</p>
                </div>
                <div class="text-right text-xs text-neutral-500">
                    Report Generated: {{ \App\Support\DateHelper::today()->format('d M Y') }}
                </div>
            </div>
        </div>

        {{-- Drive Header Card --}}
        <div class="card-box bg-white rounded-xl border border-neutral-200 p-4 sm:p-6 shadow-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-neutral-500 font-medium">Drive Title & Venue</span>
                    <p class="text-sm font-bold text-neutral-900 mt-0.5">{{ $drive->title }}</p>
                    <p class="text-neutral-600 mt-0.5">{{ $drive->venue }}</p>
                </div>
                <div>
                    <span class="text-neutral-500 font-medium">Dates (EAT)</span>
                    <p class="text-sm font-bold text-neutral-900 mt-0.5">{{ $drive->starts_on->format('D, d M Y') }}</p>
                    <p class="text-neutral-600 mt-0.5">to {{ $drive->ends_on->format('D, d M Y') }}</p>
                </div>
                <div>
                    <span class="text-neutral-500 font-medium">Capacity & Fill Rate</span>
                    <p class="text-sm font-bold text-neutral-900 mt-0.5">
                        {{ $metrics['booked'] }} / {{ $metrics['capacity'] }} places
                    </p>
                    <p class="text-neutral-600 mt-0.5">
                        Fill rate: <span class="font-semibold text-primary-600">{{ \App\Services\ReportService::formatRate($metrics['fill_rate']) }}</span>
                    </p>
                </div>
                <div>
                    <span class="text-neutral-500 font-medium">Operational Status</span>
                    <div class="mt-1">
                        <x-badge variant="{{ $drive->isPublished() ? 'success' : ($drive->isClosed() ? 'neutral' : 'warning') }}" size="sm">
                            {{ $drive->status->label() }}
                        </x-badge>
                    </div>
                    @if($metrics['has_unrecorded'])
                        <p class="text-[11px] text-amber-700 font-medium mt-1">
                            * Provisional: {{ $metrics['unrecorded_count'] }} appointment(s) await attendance close-out.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Funnel Breakdown Grid --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-neutral-900">Attendance & Outcome Funnel</h2>
                <span class="text-xs text-neutral-500">Reconciled with Roster Counter State</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-center">
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Total Capacity</span>
                    <p class="text-xl font-bold text-neutral-900 mt-1">{{ $metrics['capacity'] }}</p>
                    <span class="text-[10px] text-neutral-400 mt-0.5 block">Configured slots</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Booked</span>
                    <p class="text-xl font-bold text-primary-700 mt-1">{{ $metrics['booked'] }}</p>
                    <span class="text-[10px] text-primary-600 font-semibold mt-0.5 block">
                        {{ \App\Services\ReportService::formatRate($metrics['fill_rate']) }} fill
                    </span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Cancelled</span>
                    <p class="text-xl font-bold text-neutral-500 mt-1">{{ $metrics['cancelled'] }}</p>
                    <span class="text-[10px] text-neutral-400 mt-0.5 block">Places released</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Checked In</span>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ $metrics['checked_in'] }}</p>
                    <span class="text-[10px] text-neutral-400 mt-0.5 block">Arrived on site</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Attended</span>
                    <p class="text-xl font-bold text-amber-700 mt-1">{{ $metrics['attended'] }}</p>
                    <span class="text-[10px] text-amber-600 font-semibold mt-0.5 block">
                        {{ \App\Services\ReportService::formatRate($metrics['attendance_rate']) }} att.
                    </span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Donated</span>
                    <p class="text-xl font-bold text-emerald-700 mt-1">{{ $metrics['donated'] }}</p>
                    <span class="text-[10px] text-emerald-600 font-semibold mt-0.5 block">
                        {{ \App\Services\ReportService::formatRate($metrics['donation_rate']) }} don.
                    </span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">Did Not Donate</span>
                    <p class="text-xl font-bold text-neutral-700 mt-1">{{ $metrics['deferred'] }}</p>
                    <span class="text-[10px] text-neutral-400 mt-0.5 block">Not donated</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-neutral-200 card-box">
                    <span class="text-xs text-neutral-500 font-medium block">No-Show</span>
                    <p class="text-xl font-bold text-rose-700 mt-1">{{ $metrics['no_show'] }}</p>
                    <span class="text-[10px] text-neutral-400 mt-0.5 block">Missed</span>
                </div>
            </div>

            {{-- Walk-in Note Strip --}}
            <div class="mt-2 text-right">
                <span class="text-xs text-neutral-600 font-medium">
                    Walk-ins recorded: <strong class="text-purple-700">{{ $metrics['walk_ins'] }}</strong> (handled separately; excluded from appointment denominator)
                </span>
            </div>
        </div>

        {{-- By-Hour SVG Bar Distribution Chart --}}
        @php
            $hourData = $metrics['by_hour'];
            $maxHourCount = max(array_merge([1], array_map(fn($h) => $h['count'], $hourData)));
            $hourSvgWidth = 680;
            $hourSvgHeight = 160;
            $hourPadL = 35;
            $hourPadR = 15;
            $hourPadT = 20;
            $hourPadB = 30;
            $hourPlotW = $hourSvgWidth - $hourPadL - $hourPadR;
            $hourPlotH = $hourSvgHeight - $hourPadT - $hourPadB;
            $hCount = count($hourData);
            $hStep = $hCount > 0 ? $hourPlotW / $hCount : $hourPlotW;
            $hBarW = max(min($hStep * 0.6, 28), 10);
        @endphp
        <div class="card-box bg-white rounded-xl border border-neutral-200 p-4 sm:p-6 shadow-xs">
            <div class="mb-3">
                <h3 class="text-sm font-bold text-neutral-900">Bookings by Hour of Day</h3>
                <p class="text-xs text-neutral-500">Distribution of donor appointments throughout operating hours (EAT)</p>
            </div>

            <div class="w-full overflow-x-auto">
                <div class="min-w-[450px]">
                    <svg
                        class="w-full h-auto"
                        viewBox="0 0 {{ $hourSvgWidth }} {{ $hourSvgHeight }}"
                        role="img"
                        aria-label="Bar chart showing bookings distributed by operating hour of the day."
                    >
                        <title>Bookings by Hour</title>
                        <desc>
                            @foreach($hourData as $h)
                                {{ $h['label'] }}: {{ $h['count'] }} bookings.
                            @endforeach
                        </desc>

                        {{-- Baseline --}}
                        <line x1="{{ $hourPadL }}" y1="{{ $hourPadT + $hourPlotH }}" x2="{{ $hourSvgWidth - $hourPadR }}" y2="{{ $hourPadT + $hourPlotH }}" stroke="#E5E7EB" stroke-width="1" />

                        @foreach($hourData as $idx => $h)
                            @php
                                $xCenter = $hourPadL + ($idx * $hStep) + ($hStep / 2);
                                $bH = ($h['count'] / $maxHourCount) * $hourPlotH;
                                $yB = $hourPadT + $hourPlotH - $bH;
                                $xB = $xCenter - ($hBarW / 2);
                            @endphp

                            <rect
                                x="{{ $xB }}"
                                y="{{ $yB }}"
                                width="{{ $hBarW }}"
                                height="{{ max($bH, 2) }}"
                                fill="#2563EB"
                                rx="2"
                            >
                                <title>{{ $h['label'] }}: {{ $h['count'] }} bookings</title>
                            </rect>

                            @if($h['count'] > 0)
                                <text
                                    x="{{ $xCenter }}"
                                    y="{{ $yB - 3 }}"
                                    font-size="9"
                                    font-weight="600"
                                    text-anchor="middle"
                                    fill="#1D4ED8"
                                    font-family="system-ui, sans-serif"
                                >{{ $h['count'] }}</text>
                            @endif

                            <text
                                x="{{ $xCenter }}"
                                y="{{ $hourSvgHeight - 12 }}"
                                font-size="9"
                                text-anchor="middle"
                                fill="#6B7280"
                                font-family="system-ui, sans-serif"
                            >{{ $h['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </div>
        </div>

        {{-- By-Slot Performance Table --}}
        <div class="card-box bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            <div class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-neutral-900">Slot Performance Breakdown</h3>
                    <p class="text-xs text-neutral-500">Analysis of capacity, live bookings, attendance, and no-shows per slot</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-xs">
                    <thead class="bg-neutral-50 text-neutral-700 font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">Time Slot (EAT)</th>
                            <th class="px-4 py-3 text-right">Capacity</th>
                            <th class="px-4 py-3 text-right">Booked</th>
                            <th class="px-4 py-3 text-right">Attended</th>
                            <th class="px-4 py-3 text-right">No-Show</th>
                            <th class="px-4 py-3 text-right">Fill Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 text-neutral-800">
                        @forelse($metrics['by_slot'] as $slotRow)
                            @php
                                $fillP = $slotRow['capacity'] > 0 ? round(($slotRow['booked'] / $slotRow['capacity']) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-neutral-50">
                                <td class="px-4 py-3 font-semibold text-neutral-900">{{ $slotRow['label'] }}</td>
                                <td class="px-4 py-3 text-right text-neutral-600">{{ $slotRow['capacity'] }}</td>
                                <td class="px-4 py-3 text-right font-bold text-primary-700">{{ $slotRow['booked'] }}</td>
                                <td class="px-4 py-3 text-right font-bold text-amber-700">{{ $slotRow['attended'] }}</td>
                                <td class="px-4 py-3 text-right font-bold text-rose-700">{{ $slotRow['no_show'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $fillP >= 80 ? 'text-emerald-700' : ($fillP < 25 ? 'text-amber-700' : 'text-neutral-700') }}">
                                    {{ $fillP }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-neutral-500">No time slots configured for this drive.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-neutral-50 font-bold text-neutral-900 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-4 py-3">Total Reconciled Summary</td>
                            <td class="px-4 py-3 text-right">{{ $metrics['capacity'] }}</td>
                            <td class="px-4 py-3 text-right text-primary-700">{{ $metrics['booked'] }}</td>
                            <td class="px-4 py-3 text-right text-amber-700">{{ $metrics['attended'] }}</td>
                            <td class="px-4 py-3 text-right text-rose-700">{{ $metrics['no_show'] }}</td>
                            <td class="px-4 py-3 text-right text-primary-700">{{ \App\Services\ReportService::formatRate($metrics['fill_rate']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-layouts.staff>
