<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\LogsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EmailChangeNotice extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public User $user,
        public string $maskedEmail,
    ) {
        $this->dedupeKey = "email_change_notice:user:{$user->id}:".floor(time() / 3600);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Notice: Email address change requested';

        $this->recordSent($this->user->id, 'email_change_notice', User::class, $this->user->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.email-change-notice', [
                'subject' => $subject,
                'heading' => 'Email Change Requested',
                'donorName' => $this->user->name,
                'maskedEmail' => $this->maskedEmail,
                'actionText' => 'Review Account Security',
                'actionUrl' => route('donor.profile'),
            ])
            ->text('emails.email-change-notice-text', [
                'subject' => $subject,
                'heading' => 'Email Change Requested',
                'donorName' => $this->user->name,
                'maskedEmail' => $this->maskedEmail,
                'actionText' => 'Review Account Security',
                'actionUrl' => route('donor.profile'),
            ]);
    }

    public function failed(Throwable $e): void
    {
        $this->recordFailed($e, $this->user->id, 'email_change_notice', User::class, $this->user->id);
    }
}
