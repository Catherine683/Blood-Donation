<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a paginated listing of logged notifications.
     */
    public function index(Request $request): View
    {
        $query = NotificationLog::query()
            ->with('user')
            ->orderByDesc('id');

        // Filter by type
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Filter by status
        if ($status = $request->input('status')) {
            if ($status !== 'all' && NotificationStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        // Filter by date (EAT or creation date)
        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        $logs = $query->paginate(20)->withQueryString();

        $availableTypes = [
            'appointment_confirmed' => 'Appointment Confirmed',
            'appointment_rescheduled' => 'Appointment Rescheduled',
            'appointment_cancelled' => 'Appointment Cancelled',
            'reminder_24h' => '24-Hour Reminder',
            'reminder_2h' => '2-Hour Reminder',
            'new_drive_announcement' => 'New Drive Announcement',
        ];

        return view('pages.admin.notifications', [
            'logs' => $logs,
            'availableTypes' => $availableTypes,
            'statuses' => NotificationStatus::cases(),
        ]);
    }
}
