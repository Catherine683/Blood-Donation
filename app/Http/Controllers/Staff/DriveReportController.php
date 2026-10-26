<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Drive;
use App\Services\CsvExportService;
use App\Services\ReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveReportController extends Controller
{
    /**
     * Show printable Drive Report with Funnel and By-Slot analysis.
     */
    public function show(Drive $drive, ReportService $reportService): View
    {
        Gate::authorize('manage', $drive);

        $metrics = $reportService->getDriveMetrics($drive);

        return view('pages.staff.drives.report', [
            'drive' => $drive,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Export Drive Summary CSV.
     */
    public function exportSummary(Drive $drive, CsvExportService $exporter): StreamedResponse
    {
        Gate::authorize('manage', $drive);

        return $exporter->exportDriveSummary($drive, auth()->user());
    }

    /**
     * Export Drive Attendance Roster CSV.
     */
    public function exportRoster(Drive $drive, CsvExportService $exporter): StreamedResponse
    {
        Gate::authorize('manage', $drive);

        return $exporter->exportDriveRoster($drive, auth()->user());
    }
}
