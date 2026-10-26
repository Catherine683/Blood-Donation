<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Drive;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Handle the incoming request for donor dashboard home.
     */
    public function __invoke(): View
    {
        $nowUtc = Carbon::now('UTC')->toDateTimeString();

        $user = auth()->user();

        // Soonest active booked appointment whose slot has not ended
        $nextAppointment = $user->appointments()
            ->where('status', AppointmentStatus::Booked->value)
            ->whereHas('slot', function ($query) use ($nowUtc) {
                $query->where('ends_at', '>=', $nowUtc);
            })
            ->with(['slot.drive'])
            ->get()
            ->sortBy(fn ($appt) => $appt->slot?->starts_at)
            ->first();

        $upcomingDrives = Drive::published()
            ->upcoming()
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
            ->take(3)
            ->get();

        return view('pages.donor.home', compact('nextAppointment', 'upcomingDrives'));
    }
}
