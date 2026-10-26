<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardRedirectController
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()?->isStaff()
            ? redirect()->route('staff.dashboard')
            : redirect()->route('donor.home');
    }
}
