<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MailBudget;
use App\Services\SystemStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemStatusController extends Controller
{
    /**
     * Show operational system telemetry and background worker health.
     */
    public function __invoke(
        Request $request,
        SystemStatusService $systemStatus,
        MailBudget $mailBudget
    ): View {
        $telemetry = $systemStatus->getTelemetry($mailBudget);

        return view('pages.admin.system', compact('telemetry'));
    }
}
