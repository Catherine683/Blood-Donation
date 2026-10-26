@props([
    'data' => [], // array of {week_label, count}
])

@php
    $hasData = !empty($data);
    $maxVal = $hasData ? max(array_map(fn($d) => $d['count'], $data)) : 10;
    $yMax = max((int) ceil($maxVal * 1.2), 5);

    $svgWidth = 720;
    $svgHeight = 280;
    $paddingLeft = 45;
    $paddingRight = 20;
    $paddingTop = 30;
    $paddingBottom = 55;
    $plotWidth = $svgWidth - $paddingLeft - $paddingRight;
    $plotHeight = $svgHeight - $paddingTop - $paddingBottom;

    $count = count($data);
    $barWidth = $count > 0 ? max(min(($plotWidth / $count) * 0.5, 40), 12) : 24;
@endphp

<div x-data="{ showTable: false }" class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-6 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
            <h3 class="text-base font-semibold text-neutral-900">Bookings Over Time</h3>
            <p class="text-xs text-neutral-500">Weekly trend of appointment reservations</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button
                type="button"
                @click="showTable = !showTable"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-hidden focus:ring-2 focus:ring-primary-500 transition-colors"
                aria-expanded="false"
                :aria-expanded="showTable"
            >
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span x-text="showTable ? 'View Chart' : 'View as Table'">View as Table</span>
            </button>
        </div>
    </div>

    @if(!$hasData)
        <div class="py-12 text-center text-sm text-neutral-500">
            <p class="font-medium text-neutral-700">No booking trend data recorded in this period.</p>
            <p class="text-xs text-neutral-400 mt-1">Booked appointments for drives in this range will appear here.</p>
        </div>
    @else
        {{-- Chart View --}}
        <div x-show="!showTable" class="w-full overflow-x-auto">
            <div class="min-w-[450px]">
                <svg
                    class="w-full h-auto"
                    viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}"
                    role="img"
                    aria-label="Bar chart showing weekly count of booked donor appointments."
                >
                    <title>Weekly Bookings Chart</title>
                    <desc>
                        @foreach($data as $d)
                            {{ $d['week_label'] }}: {{ $d['count'] }} bookings.
                        @endforeach
                    </desc>

                    {{-- Y-Axis Grid Lines --}}
                    @for($i = 0; $i <= 4; $i++)
                        @php
                            $val = round(($yMax / 4) * $i);
                            $yPos = $paddingTop + $plotHeight - ($plotHeight * ($i / 4));
                        @endphp
                        <line
                            x1="{{ $paddingLeft }}"
                            y1="{{ $yPos }}"
                            x2="{{ $svgWidth - $paddingRight }}"
                            y2="{{ $yPos }}"
                            stroke="#E5E7EB"
                            stroke-dasharray="{{ $i === 0 ? '0' : '3,3' }}"
                            stroke-width="1"
                        />
                        <text
                            x="{{ $paddingLeft - 8 }}"
                            y="{{ $yPos + 4 }}"
                            font-size="10"
                            text-anchor="end"
                            fill="#6B7280"
                            font-family="system-ui, sans-serif"
                        >{{ $val }}</text>
                    @endfor

                    {{-- Bars --}}
                    @foreach($data as $idx => $d)
                        @php
                            $step = $plotWidth / $count;
                            $xCenter = $paddingLeft + ($idx * $step) + ($step / 2);
                            $barHeight = ($d['count'] / $yMax) * $plotHeight;
                            $yBar = $paddingTop + $plotHeight - $barHeight;
                            $xBar = $xCenter - ($barWidth / 2);
                        @endphp

                        <rect
                            x="{{ $xBar }}"
                            y="{{ $yBar }}"
                            width="{{ $barWidth }}"
                            height="{{ max($barHeight, 2) }}"
                            fill="#7C3AED"
                            rx="3"
                        >
                            <title>{{ $d['week_label'] }}: {{ $d['count'] }} appointments</title>
                        </rect>

                        {{-- Value Label --}}
                        @if($d['count'] > 0)
                            <text
                                x="{{ $xCenter }}"
                                y="{{ $yBar - 5 }}"
                                font-size="10"
                                font-weight="600"
                                text-anchor="middle"
                                fill="#6D28D9"
                                font-family="system-ui, sans-serif"
                            >{{ $d['count'] }}</text>
                        @endif

                        {{-- X Label --}}
                        <text
                            x="{{ $xCenter }}"
                            y="{{ $svgHeight - 20 }}"
                            font-size="10"
                            text-anchor="middle"
                            fill="#374151"
                            font-family="system-ui, sans-serif"
                        >
                            {{ $d['week_label'] }}
                        </text>
                    @endforeach
                </svg>
            </div>
        </div>

        {{-- Accessible Table Alternative --}}
        <div x-show="showTable" x-cloak class="overflow-x-auto mt-2">
            <table class="min-w-full divide-y divide-neutral-200 text-xs">
                <thead class="bg-neutral-50 text-neutral-700 font-semibold">
                    <tr>
                        <th class="px-3 py-2 text-left">Week Interval</th>
                        <th class="px-3 py-2 text-right">Appointments Booked</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 text-neutral-800">
                    @foreach($data as $d)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-3 py-2 font-medium">{{ $d['week_label'] }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-purple-700">{{ $d['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
