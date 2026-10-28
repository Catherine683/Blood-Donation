<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
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

        $user = auth()->user()->loadMissing('donorProfile');
        $donorProfile = $user->donorProfile;

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

        // Total active upcoming appointments count
        $activeAppointmentsCount = $user->appointments()
            ->where('status', AppointmentStatus::Booked->value)
            ->whereHas('slot', function ($query) use ($nowUtc) {
                $query->where('ends_at', '>=', $nowUtc);
            })
            ->count();

        // Lifetime verified donations (where outcome is Donated)
        $donationsCount = $user->donationRecords()
            ->where('outcome', DonationOutcome::Donated->value)
            ->count();

        // Estimated lives impacted (1 unit saves up to 3 lives)
        $livesImpacted = $donationsCount * 3;

        // Recent donation records
        $recentDonations = $user->donationRecords()
            ->with(['drive', 'recordedBy'])
            ->latest('recorded_at')
            ->take(5)
            ->get();

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

        return view('pages.donor.home', compact(
            'user',
            'donorProfile',
            'nextAppointment',
            'activeAppointmentsCount',
            'donationsCount',
            'livesImpacted',
            'recentDonations',
            'upcomingDrives',
        ));
    }
}
