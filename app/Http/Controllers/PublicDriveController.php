<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\DriveStatus;
use App\Models\Drive;
use App\Support\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PublicDriveController extends Controller
{
    /**
     * Display a listing of upcoming published drives.
     */
    public function index(Request $request): View
    {
        $todayEat = DateHelper::today()->toDateString();
        $nowUtc = Carbon::now('UTC')->toDateTimeString();

        // Optimized query: fetches published upcoming drives with aggregated slot availability in subqueries (0 N+1)
        $drives = Drive::query()
            ->where('status', DriveStatus::Published->value)
            ->where('ends_on', '>=', $todayEat)
            ->withSum([
                'slots as upcoming_capacity' => function ($query) use ($nowUtc) {
                    $query->where('starts_at', '>=', $nowUtc);
                },
            ], 'capacity')
            ->withSum([
                'slots as upcoming_booked' => function ($query) use ($nowUtc) {
                    $query->where('starts_at', '>=', $nowUtc);
                },
            ], 'booked_count')
            ->withCount([
                'slots as upcoming_slots_count' => function ($query) use ($nowUtc) {
                    $query->where('starts_at', '>=', $nowUtc);
                },
            ])
            ->orderBy('starts_on')
            ->orderBy('id')
            ->paginate(9);

        return view('pages.public.drives.index', compact('drives'));
    }

    /**
     * Display the specified drive with slot availability and booking controls.
     */
    public function show(Request $request, Drive $drive): View
    {
        // Draft drives return 404 to public, but staff & admin can preview
        if ($drive->isDraft()) {
            if (! auth()->check() || ! auth()->user()->isStaff()) {
                abort(404);
            }
        }

        // Only show upcoming slots to public
        $nowUtc = Carbon::now('UTC');
        $slots = $drive->slots()
            ->where('starts_at', '>=', $nowUtc->toDateTimeString())
            ->orderBy('starts_at')
            ->get();

        $groupedSlots = $slots->groupBy(function ($slot) {
            return DateHelper::formatDate($slot->starts_at, 'Y-m-d');
        });

        // Check if the current authenticated donor already holds a booked appointment on this drive
        $donorBooking = null;
        if (auth()->check() && auth()->user()->isDonor()) {
            $donorBooking = auth()->user()->appointments()
                ->where('status', AppointmentStatus::Booked->value)
                ->whereHas('slot', fn ($query) => $query->where('drive_id', $drive->id))
                ->with('slot')
                ->first();
        }

        $cutoffMinutes = (int) config('donorlink.booking_cutoff_minutes', 60);
        $cutoffThreshold = $nowUtc->addMinutes($cutoffMinutes);

        return view('pages.public.drives.show', compact('drive', 'slots', 'groupedSlots', 'donorBooking', 'cutoffThreshold', 'cutoffMinutes'));
    }
}
