<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtp extends Notification
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
            ->subject('Your DonorLink UG password reset code')
            ->view('emails.password-reset-otp', [
                'name' => $notifiable->name,
                'code' => $this->code,
            ])
            ->text('emails.password-reset-otp-text', [
                'name' => $notifiable->name,
                'code' => $this->code,
            ]);
    }
}
