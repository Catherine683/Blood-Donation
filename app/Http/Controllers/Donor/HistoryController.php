<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Enums\DonationOutcome;
use App\Http\Controllers\Controller;
use App\Support\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Display the voluntary donor's historical donation outcomes.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $records = $user->donationRecords()
            ->with('drive')
            ->orderByDesc('recorded_at')
            ->get();

        $donationsCount = $records->where('outcome', DonationOutcome::Donated)->count();
        $donorProfile = $user->donorProfile;
        $lastDonatedDate = $donorProfile?->last_donated_on;

        $livesImpacted = $donationsCount * 3;
        $totalVolumeMl = $donationsCount * 450;

        // Next suggested donation window (minimum 90 days following last verified whole blood donation)
        $suggestedNextDonationDate = $lastDonatedDate
            ? Carbon::parse($lastDonatedDate)->addDays(90)
            : null;

        return view('pages.donor.history', [
            'user' => $user,
            'records' => $records,
            'donorProfile' => $donorProfile,
            'donationsCount' => $donationsCount,
            'livesImpacted' => $livesImpacted,
            'totalVolumeMl' => $totalVolumeMl,
            'lastDonatedOn' => $lastDonatedDate ? DateHelper::formatDate($lastDonatedDate) : null,
            'suggestedNextDonationDate' => $suggestedNextDonationDate ? DateHelper::formatDate($suggestedNextDonationDate) : null,
        ]);
    }
}
