<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    /**
     * Show confirmation page before unsubscribing a donor from announcements.
     */
    public function confirm(Request $request, User $user): View
    {
        return view('pages.public.unsubscribe-confirm', [
            'user' => $user,
        ]);
    }

    /**
     * Unsubscribe a donor from new drive announcements via signed POST request.
     */
    public function unsubscribe(Request $request, User $user): View
    {
        $user->notify_new_drives = false;
        $user->save();

        return view('pages.public.unsubscribed', [
            'user' => $user,
        ]);
    }
}
