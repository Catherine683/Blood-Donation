<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\LogsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonorActivationLink extends Notification
{
    use LogsNotification, Queueable;

    public function __construct(
        public readonly User $user,
        public readonly string $activationUrl,
    ) {
        $this->dedupeKey = "donor_activation:user:{$user->id}:".floor(time() / 3600);
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Activate your DonorLink UG account';

        $this->recordSent($this->user->id, 'donor_activation', User::class, $this->user->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.donor-activation', [
                'subject' => $subject,
                'userName' => $this->user->name,
                'actionText' => 'Activate Donor Account',
                'actionUrl' => $this->activationUrl,
            ])
            ->text('emails.donor-activation-text', [
                'subject' => $subject,
                'userName' => $this->user->name,
                'actionText' => 'Activate Donor Account',
                'actionUrl' => $this->activationUrl,
            ]);
    }
}
