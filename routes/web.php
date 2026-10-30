<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DonationExportController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\SystemStatusController;
use App\Http\Controllers\Auth\DonorActivationController;
use App\Http\Controllers\Auth\DonorRegistrationController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Donor\AppointmentController;
use App\Http\Controllers\Donor\HistoryController;
use App\Http\Controllers\Donor\HomeController as DonorHomeController;
use App\Http\Controllers\Donor\PasswordController;
use App\Http\Controllers\Donor\ProfileController;
use App\Http\Controllers\PublicDriveController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\DonorController;
use App\Http\Controllers\Staff\DriveController as StaffDriveController;
use App\Http\Controllers\Staff\DriveReportController;
use App\Http\Controllers\Staff\InvitationController;
use App\Http\Controllers\Staff\SlotController as StaffSlotController;
use App\Http\Controllers\UnsubscribeController;
use App\Livewire\Admin\StaffManagement;
use App\Livewire\Staff\DriveRoster;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $todayEat = \App\Support\DateHelper::today()->toDateString();
    $upcomingDrives = \App\Models\Drive::query()
        ->where('status', \App\Enums\DriveStatus::Published->value)
        ->where('ends_on', '>=', $todayEat)
        ->orderBy('starts_on')
        ->take(3)
        ->get();

    return view('welcome', compact('upcomingDrives'));
})->name('home');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Public drives browsing
Route::get('/drives', [PublicDriveController::class, 'index'])->name('drives.index');
Route::get('/drives/{drive}', [PublicDriveController::class, 'show'])->name('drives.show');

// Unsubscribe from announcements (signed URL)
Route::get('/unsubscribe/announcements/{user}', [UnsubscribeController::class, 'confirm'])
    ->name('unsubscribe.announcements')
    ->middleware(['signed', 'throttle:unsubscribe']);

Route::post('/unsubscribe/announcements/{user}', [UnsubscribeController::class, 'unsubscribe'])
    ->name('unsubscribe.announcements.post')
    ->middleware(['signed', 'throttle:unsubscribe']);

Route::post('/register', [DonorRegistrationController::class, 'store'])
    ->middleware(['web', 'guest', 'throttle:registration'])
    ->name('register.store');

Route::get('/register/otp', [DonorRegistrationController::class, 'create'])
    ->middleware(['web', 'guest'])
    ->name('register.otp');

Route::post('/register/otp', [DonorRegistrationController::class, 'verify'])
    ->middleware(['web', 'guest', 'throttle:registration-otp-verify'])
    ->name('register.otp.verify');

Route::post('/register/otp/resend', [DonorRegistrationController::class, 'resend'])
    ->middleware(['web', 'guest', 'throttle:registration-otp-resend'])
    ->name('register.otp.resend');

Route::post('/forgot-password', [PasswordResetOtpController::class, 'store'])
    ->middleware(['web', 'guest', 'throttle:forgot-password'])
    ->name('password.email');

Route::get('/forgot-password/otp', [PasswordResetOtpController::class, 'create'])
    ->middleware(['web', 'guest'])
    ->name('password.otp');

Route::post('/forgot-password/otp', [PasswordResetOtpController::class, 'verify'])
    ->middleware(['web', 'guest', 'throttle:password-otp-verify'])
    ->name('password.otp.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['web', 'auth', 'throttle:verification-resend'])
    ->name('verification.send');

/*
|--------------------------------------------------------------------------
| Authenticated Dashboard Redirect
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Donor Shell Routes (/donor/*)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:donor'])
    ->prefix('donor')
    ->name('donor.')
    ->group(function () {
        Route::get('/', DonorHomeController::class)->name('home');
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments');
        Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('throttle:15,1')->name('appointments.store');
        Route::delete('/appointments/{appointment?}', [AppointmentController::class, 'destroy'])->middleware('throttle:15,1')->name('appointments.destroy');
        Route::put('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->middleware('throttle:15,1')->name('appointments.reschedule');
        Route::get('/history', HistoryController::class)->name('history');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [PasswordController::class, 'update'])->name('password.update');
        Route::post('/profile/email-change', [ProfileController::class, 'requestEmailChange'])->middleware('throttle:email-change-requests')->name('profile.email-change');
        Route::post('/profile/email-change/resend', [ProfileController::class, 'resendEmailChange'])->middleware('throttle:email-change-requests')->name('profile.email-change.resend');
        Route::post('/profile/email-change/cancel', [ProfileController::class, 'cancelEmailChange'])->name('profile.email-change.cancel');
        Route::get('/profile/export', [ProfileController::class, 'exportData'])->middleware('throttle:donor-data-download')->name('profile.export');
    });

// Verification of email change via signed link
Route::get('/donor/email/verify-change/{user}', [ProfileController::class, 'confirmEmailChange'])
    ->name('donor.email.verify-change')
    ->middleware('signed');

// Staff / Admin invitation acceptance via signed link
Route::get('/staff/invitation/{user}', [InvitationController::class, 'show'])
    ->name('staff.invitation.accept')
    ->middleware('signed');
Route::post('/staff/invitation/{user}', [InvitationController::class, 'store'])
    ->name('staff.invitation.store')
    ->middleware('signed');

// Staff-created donor activation via signed link
Route::get('/donor/activation/{user}', [DonorActivationController::class, 'show'])
    ->name('donor.activation.accept')
    ->middleware('signed');
Route::post('/donor/activation/{user}', [DonorActivationController::class, 'store'])
    ->name('donor.activation.store')
    ->middleware('signed');

/*
|--------------------------------------------------------------------------
| Staff Shell Routes (/staff/*) - accessible by staff and admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/export/period-summary', [DashboardController::class, 'exportPeriod'])->middleware('throttle:exports')->name('export.period');

        // Drives management
        Route::get('/drives', [StaffDriveController::class, 'index'])->name('drives');
        Route::get('/drives/create', [StaffDriveController::class, 'create'])->name('drives.create');
        Route::post('/drives', [StaffDriveController::class, 'store'])->name('drives.store');
        Route::get('/drives/{drive}', [StaffDriveController::class, 'show'])->name('drives.show');
        Route::get('/drives/{drive}/edit', [StaffDriveController::class, 'edit'])->name('drives.edit');
        Route::put('/drives/{drive}', [StaffDriveController::class, 'update'])->name('drives.update');
        Route::delete('/drives/{drive}', [StaffDriveController::class, 'destroy'])->name('drives.destroy');

        // Drive report & exports
        Route::get('/drives/{drive}/report', [DriveReportController::class, 'show'])->name('drives.report');
        Route::get('/drives/{drive}/export/summary', [DriveReportController::class, 'exportSummary'])->middleware('throttle:exports')->name('drives.export.summary');
        Route::get('/drives/{drive}/export/roster', [DriveReportController::class, 'exportRoster'])->middleware('throttle:exports')->name('drives.export.roster');

        // Drive attendance roster & close out
        Route::get('/drives/{drive}/roster', DriveRoster::class)->name('drives.roster');
        Route::post('/drives/{drive}/close-out', [StaffDriveController::class, 'closeOut'])->name('drives.close-out');

        // Drive state transitions
        Route::post('/drives/{drive}/publish', [StaffDriveController::class, 'publish'])->name('drives.publish');
        Route::post('/drives/{drive}/close', [StaffDriveController::class, 'close'])->name('drives.close');
        Route::post('/drives/{drive}/reopen', [StaffDriveController::class, 'reopen'])->name('drives.reopen');
        Route::post('/drives/{drive}/announcement', [StaffDriveController::class, 'sendAnnouncement'])->name('drives.announcement');

        // Slot management under a drive
        Route::post('/drives/{drive}/slots', [StaffSlotController::class, 'store'])->name('drives.slots.store');
        Route::post('/drives/{drive}/slots/preview', [StaffSlotController::class, 'preview'])->name('drives.slots.preview');
        Route::post('/drives/{drive}/slots/generate', [StaffSlotController::class, 'generate'])->name('drives.slots.generate');

        // Slot actions
        Route::put('/slots/{slot}', [StaffSlotController::class, 'update'])->name('slots.update');
        Route::delete('/slots/{slot}', [StaffSlotController::class, 'destroy'])->name('slots.destroy');

        // Donor management & outcomes
        Route::get('/donors', [DonorController::class, 'index'])->name('donors');
        Route::get('/donors/create', [DonorController::class, 'create'])->name('donors.create');
        Route::post('/donors', [DonorController::class, 'store'])->name('donors.store');
        Route::get('/donors/{user}', [DonorController::class, 'show'])->name('donors.show');
        Route::put('/donors/{user}', [DonorController::class, 'update'])->name('donors.update');
        Route::post('/donors/{user}/activation-email', [DonorController::class, 'resendActivationLink'])->middleware('throttle:invitation-resend')->name('donors.activation-email');
        Route::post('/donors/{user}/donation-records/{record}/correct', [DonorController::class, 'correctDonation'])->name('donors.records.correct');
    });

/*
|--------------------------------------------------------------------------
| Admin Shell Routes (/admin/*) - accessible by admin only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/staff', StaffManagement::class)->name('staff');
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/donations/export', [DonationExportController::class, 'export'])->middleware('throttle:exports')->name('donations.export');
        Route::get('/system', SystemStatusController::class)->name('system');
    });

require __DIR__.'/settings.php';
