<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonorRegistrationOtp extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your DonorLink UG email verification code')
            ->view('emails.donor-registration-otp', [
                'name' => $notifiable->name,
                'code' => $this->code,
            ])
            ->text('emails.donor-registration-otp-text', [
                'name' => $notifiable->name,
                'code' => $this->code,
            ]);
    }
}
