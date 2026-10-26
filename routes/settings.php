<?php

use App\Http\Controllers\PasskeyEndpointController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'redirect.donor.settings'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'redirect.donor.settings'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', PasskeyEndpointController::class)
    ->name('well-known.passkeys');
