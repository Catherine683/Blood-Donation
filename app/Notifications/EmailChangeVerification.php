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

class EmailChangeVerification extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public User $user,
        public string $newEmail,
        public string $verificationUrl,
    ) {
        $this->dedupeKey = "email_change_verify:user:{$user->id}:".md5($newEmail.':'.floor(time() / 3600));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Verify your new DonorLink email address';

        $this->recordSent($this->user->id, 'email_change_verification', User::class, $this->user->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.email-change-verification', [
                'subject' => $subject,
                'heading' => 'Confirm New Email Address',
                'donorName' => $this->user->name,
                'newEmail' => $this->newEmail,
                'verificationUrl' => $this->verificationUrl,
                'actionText' => 'Confirm Email Address',
                'actionUrl' => $this->verificationUrl,
            ])
            ->text('emails.email-change-verification-text', [
                'subject' => $subject,
                'heading' => 'Confirm New Email Address',
                'donorName' => $this->user->name,
                'newEmail' => $this->newEmail,
                'verificationUrl' => $this->verificationUrl,
                'actionText' => 'Confirm Email Address',
                'actionUrl' => $this->verificationUrl,
            ]);
    }

    public function failed(Throwable $e): void
    {
        $this->recordFailed($e, $this->user->id, 'email_change_verification', User::class, $this->user->id);
    }
}
