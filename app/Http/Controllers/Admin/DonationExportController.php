<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvExportService;
use App\Support\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationExportController extends Controller
{
    /**
     * Export all donation records for a chosen period (Admin only).
     */
    public function export(Request $request, CsvExportService $exporter): StreamedResponse
    {
        Gate::authorize('access-admin');

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

        return $exporter->exportDonationRecords($startEat, $endEat, auth()->user());
    }
}
