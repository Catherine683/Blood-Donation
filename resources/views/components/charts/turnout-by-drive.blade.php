@props([
    'data' => [], // array of {id, title, short_title, expected, attended, donated}
])

@php
    $hasData = !empty($data);
    $chartId = 'turnout-chart-' . uniqid();
    $maxVal = $hasData ? max(array_map(fn($d) => max($d['expected'], $d['attended'], $d['donated'], 1), $data)) : 10;
    // Round max up slightly for nice ceiling
    $yMax = max((int) ceil($maxVal * 1.15), 5);

    $svgWidth = 720;
    $svgHeight = 280;
    $paddingLeft = 45;
    $paddingRight = 20;
    $paddingTop = 30;
    $paddingBottom = 55;
    $plotWidth = $svgWidth - $paddingLeft - $paddingRight;
    $plotHeight = $svgHeight - $paddingTop - $paddingBottom;

    $count = count($data);
    $groupWidth = $count > 0 ? $plotWidth / $count : $plotWidth;
    $barWidth = max(min($groupWidth * 0.22, 22), 8);
@endphp

<div x-data="{ showTable: false }" class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-6 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
            <h3 class="text-base font-semibold text-neutral-900">Turnout by Drive</h3>
            <p class="text-xs text-neutral-500">Expected vs. attended vs. donated donors for recent drives (up to 8)</p>
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

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-neutral-600 mb-4 pb-2 border-b border-neutral-100">
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-xs bg-primary-600 inline-block"></span>
            <span>Expected</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-xs bg-amber-500 inline-block"></span>
            <span>Attended</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-xs bg-emerald-600 inline-block"></span>
            <span>Donated</span>
        </div>
    </div>

    @if(!$hasData)
        <div class="py-12 text-center text-sm text-neutral-500">
            <p class="font-medium text-neutral-700">No drive turnout data in this period.</p>
            <p class="text-xs text-neutral-400 mt-1">Drives held in the selected timeframe will appear here.</p>
        </div>
    @else
        {{-- Chart Container --}}
        <div x-show="!showTable" class="w-full overflow-x-auto">
            <div class="min-w-[500px]">
                <svg
                    class="w-full h-auto"
                    viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}"
                    role="img"
                    aria-label="Grouped bar chart showing expected, attended, and donated donors across recent drives."
                >
                    <title>Drive Turnout Chart</title>
                    <desc>
                        @foreach($data as $d)
                            {{ $d['title'] }}: Expected {{ $d['expected'] }}, Attended {{ $d['attended'] }}, Donated {{ $d['donated'] }}.
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

                    {{-- Groups of Bars --}}
                    @foreach($data as $idx => $d)
                        @php
                            $groupCenterX = $paddingLeft + ($idx * $groupWidth) + ($groupWidth / 2);
                            $hExp = ($d['expected'] / $yMax) * $plotHeight;
                            $hAtt = ($d['attended'] / $yMax) * $plotHeight;
                            $hDon = ($d['donated'] / $yMax) * $plotHeight;

                            $yExp = $paddingTop + $plotHeight - $hExp;
                            $yAtt = $paddingTop + $plotHeight - $hAtt;
                            $yDon = $paddingTop + $plotHeight - $hDon;

                            $xExp = $groupCenterX - ($barWidth * 1.5) - 2;
                            $xAtt = $groupCenterX - ($barWidth * 0.5);
                            $xDon = $groupCenterX + ($barWidth * 0.5) + 2;
                        @endphp

                        {{-- Expected Bar --}}
                        <rect
                            x="{{ $xExp }}"
                            y="{{ $yExp }}"
                            width="{{ $barWidth }}"
                            height="{{ max($hExp, 2) }}"
                            fill="#2563EB"
                            rx="2"
                        >
                            <title>{{ $d['title'] }} - Expected: {{ $d['expected'] }}</title>
                        </rect>
                        @if($d['expected'] > 0)
                            <text
                                x="{{ $xExp + ($barWidth / 2) }}"
                                y="{{ $yExp - 3 }}"
                                font-size="9"
                                font-weight="600"
                                text-anchor="middle"
                                fill="#1E40AF"
                                font-family="system-ui, sans-serif"
                            >{{ $d['expected'] }}</text>
                        @endif

                        {{-- Attended Bar --}}
                        <rect
                            x="{{ $xAtt }}"
                            y="{{ $yAtt }}"
                            width="{{ $barWidth }}"
                            height="{{ max($hAtt, 2) }}"
                            fill="#F59E0B"
                            rx="2"
                        >
                            <title>{{ $d['title'] }} - Attended: {{ $d['attended'] }}</title>
                        </rect>
                        @if($d['attended'] > 0)
                            <text
                                x="{{ $xAtt + ($barWidth / 2) }}"
                                y="{{ $yAtt - 3 }}"
                                font-size="9"
                                font-weight="600"
                                text-anchor="middle"
                                fill="#B45309"
                                font-family="system-ui, sans-serif"
                            >{{ $d['attended'] }}</text>
                        @endif

                        {{-- Donated Bar --}}
                        <rect
                            x="{{ $xDon }}"
                            y="{{ $yDon }}"
                            width="{{ $barWidth }}"
                            height="{{ max($hDon, 2) }}"
                            fill="#059669"
                            rx="2"
                        >
                            <title>{{ $d['title'] }} - Donated: {{ $d['donated'] }}</title>
                        </rect>
                        @if($d['donated'] > 0)
                            <text
                                x="{{ $xDon + ($barWidth / 2) }}"
                                y="{{ $yDon - 3 }}"
                                font-size="9"
                                font-weight="600"
                                text-anchor="middle"
                                fill="#047857"
                                font-family="system-ui, sans-serif"
                            >{{ $d['donated'] }}</text>
                        @endif

                        {{-- X-Axis Label --}}
                        <text
                            x="{{ $groupCenterX }}"
                            y="{{ $svgHeight - 20 }}"
                            font-size="10"
                            text-anchor="middle"
                            fill="#374151"
                            font-family="system-ui, sans-serif"
                        >
                            {{ $d['short_title'] }}
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
                        <th class="px-3 py-2 text-left">Blood Drive</th>
                        <th class="px-3 py-2 text-right">Expected</th>
                        <th class="px-3 py-2 text-right">Attended</th>
                        <th class="px-3 py-2 text-right">Donated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 text-neutral-800">
                    @foreach($data as $d)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-3 py-2 font-medium">
                                <a href="{{ route('staff.drives.report', $d['id']) }}" class="text-primary-600 hover:underline">
                                    {{ $d['title'] }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-primary-700">{{ $d['expected'] }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-amber-700">{{ $d['attended'] }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-emerald-700">{{ $d['donated'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
