<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\CsvExportService;
use App\Services\ReportService;
use App\Support\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Staff Operations Analytics Dashboard.
     */
    public function index(Request $request, ReportService $reportService): View
    {
        Gate::authorize('access-staff');

        $preset = $request->input('preset', '30d');
        $todayEat = DateHelper::today();

        [$startEat, $endEat] = match ($preset) {
            '90d' => [$todayEat->copy()->subDays(89)->startOfDay(), $todayEat->copy()->endOfDay()],
            'this_year' => [$todayEat->copy()->startOfYear(), $todayEat->copy()->endOfDay()],
            'custom' => [
                Carbon::parse($request->input('start_date', $todayEat->copy()->subDays(29)->toDateString()), 'Africa/Kampala')->startOfDay(),
                Carbon::parse($request->input('end_date', $todayEat->toDateString()), 'Africa/Kampala')->endOfDay(),
            ],
            default => [$todayEat->copy()->subDays(29)->startOfDay(), $todayEat->copy()->endOfDay()],
        };

        // Ensure start is not after end
        if ($startEat->gt($endEat)) {
            $startEat = $endEat->copy()->subDays(29)->startOfDay();
        }

        $periodMetrics = $reportService->getPeriodMetrics($startEat, $endEat);
        $liveOrNext = $reportService->getLiveOrNextDrive();
        $needsAttention = $reportService->getNeedsAttentionItems();
        $donorBase = $reportService->getDonorBaseAggregates();

        return view('pages.staff.dashboard', [
            'preset' => $preset,
            'startEat' => $startEat,
            'endEat' => $endEat,
            'periodMetrics' => $periodMetrics,
            'liveOrNext' => $liveOrNext,
            'needsAttention' => $needsAttention,
            'donorBase' => $donorBase,
        ]);
    }

    /**
     * Export Period Summary CSV.
     */
    public function exportPeriod(Request $request, CsvExportService $exporter): StreamedResponse
    {
        Gate::authorize('access-staff');

        $todayEat = DateHelper::today();
        $preset = $request->input('preset', '30d');

        [$startEat, $endEat] = match ($preset) {
            '90d' => [$todayEat->copy()->subDays(89)->startOfDay(), $todayEat->copy()->endOfDay()],
            'this_year' => [$todayEat->copy()->startOfYear(), $todayEat->copy()->endOfDay()],
            'custom' => [
                Carbon::parse($request->input('start_date', $todayEat->copy()->subDays(29)->toDateString()), 'Africa/Kampala')->startOfDay(),
                Carbon::parse($request->input('end_date', $todayEat->toDateString()), 'Africa/Kampala')->endOfDay(),
            ],
            default => [$todayEat->copy()->subDays(29)->startOfDay(), $todayEat->copy()->endOfDay()],
        };

        return $exporter->exportPeriodSummary($startEat, $endEat, auth()->user());
    }
}
