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
        $search = $request->string('q')->trim()->toString();
        $filter = $request->string('filter', 'all')->trim()->toString();

        // Optimized query: fetches published upcoming drives with aggregated slot availability in subqueries (0 N+1)
        $query = Drive::query()
            ->where('status', DriveStatus::Published->value)
            ->where('ends_on', '>=', $todayEat)
            ->withSum([
                'slots as upcoming_capacity' => function ($q) use ($nowUtc) {
                    $q->where('starts_at', '>=', $nowUtc);
                },
            ], 'capacity')
            ->withSum([
                'slots as upcoming_booked' => function ($q) use ($nowUtc) {
                    $q->where('starts_at', '>=', $nowUtc);
                },
            ], 'booked_count')
            ->withCount([
                'slots as upcoming_slots_count' => function ($q) use ($nowUtc) {
                    $q->where('starts_at', '>=', $nowUtc);
                },
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($filter === 'available') {
            $query->whereHas('slots', function ($q) use ($nowUtc) {
                $q->where('starts_at', '>=', $nowUtc)
                    ->whereColumn('booked_count', '<', 'capacity');
            });
        }

        $drives = $query->orderBy('starts_on')
            ->orderBy('id')
            ->paginate(9)
            ->withQueryString();

        return view('pages.public.drives.index', compact('drives', 'search', 'filter'));
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
