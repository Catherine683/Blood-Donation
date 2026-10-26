<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Enums\DonationOutcome;
use App\Http\Controllers\Controller;
use App\Support\DateHelper;
use Illuminate\Http\Request;
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
        $lastDonatedDate = $user->donorProfile?->last_donated_on;

        return view('pages.donor.history', [
            'records' => $records,
            'donationsCount' => $donationsCount,
            'lastDonatedOn' => $lastDonatedDate ? DateHelper::formatDate($lastDonatedDate) : null,
        ]);
    }
}
