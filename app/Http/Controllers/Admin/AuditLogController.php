<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display the paginated compliance audit log.
     */
    public function index(Request $request): View
    {
        $actionFilter = $request->input('action');
        $actorFilter = $request->input('actor_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = AuditLog::query()->with('actor')->orderByDesc('created_at');

        if (! empty($actionFilter)) {
            $query->where('action', $actionFilter);
        }

        if (! empty($actorFilter)) {
            $query->where('actor_id', $actorFilter);
        }

        if (! empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Populate filter options
        $distinctActions = AuditLog::query()->distinct()->pluck('action')->sort()->values();
        $actors = User::whereIn('id', AuditLog::query()->distinct()->whereNotNull('actor_id')->pluck('actor_id'))
            ->orderBy('name')
            ->get();

        return view('pages.admin.audit-log', [
            'logs' => $logs,
            'distinctActions' => $distinctActions,
            'actors' => $actors,
            'actionFilter' => $actionFilter,
            'actorFilter' => $actorFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
