<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Listeners\SendAppointmentCancelledNotification;
use App\Listeners\SendAppointmentConfirmedNotification;
use App\Listeners\SendAppointmentRescheduledNotification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureBrandedEmails();
        $this->configureEvents();
    }

    /**
     * Configure event listeners.
     */
    protected function configureEvents(): void
    {
        Event::listen(
            AppointmentBooked::class,
            SendAppointmentConfirmedNotification::class,
        );

        Event::listen(
            AppointmentRescheduled::class,
            SendAppointmentRescheduledNotification::class,
        );

        Event::listen(
            AppointmentCancelled::class,
            SendAppointmentCancelledNotification::class,
        );
    }

    /**
     * Configure branded email templates for auth notifications.
     */
    protected function configureBrandedEmails(): void
    {
        Blade::component('emails.layout', 'mail-layout');

        // Share the active mail message instance with child components (e.g. mail-layout)
        View::composer('emails.*', function ($view): void {
            if (isset($view['message'])) {
                View::share('__active_mail_message', $view['message']);
            }
        });

        View::composer('emails.layout', function ($view): void {
            if (! isset($view['message']) && View::shared('__active_mail_message')) {
                $view->with('message', View::shared('__active_mail_message'));
            }
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirm your email for DonorLink UG')
                ->view(['html' => 'emails.verify-email', 'text' => 'emails.verify-email-text'], [
                    'user' => $notifiable,
                    'url' => $url,
                ]);
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset your password for DonorLink UG')
                ->view(['html' => 'emails.reset-password', 'text' => 'emails.reset-password-text'], [
                    'user' => $notifiable,
                    'url' => $url,
                ]);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure authorization gates.
     */
    protected function configureGates(): void
    {
        Gate::define('access-donor', fn (User $user): bool => $user->is_active && $user->isDonor());
        Gate::define('access-staff', fn (User $user): bool => $user->is_active && $user->isStaff());
        Gate::define('access-admin', fn (User $user): bool => $user->is_active && $user->isAdmin());
    }
}
