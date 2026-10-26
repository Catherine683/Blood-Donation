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

class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public User $user,
        public string $invitationUrl,
    ) {
        $this->dedupeKey = "staff_invitation:user:{$user->id}:".floor(time() / 3600);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleName = $this->user->isAdmin() ? 'Administrator' : 'Staff Member';
        $subject = "Invitation to access UBTS DonorLink ({$roleName})";

        $this->recordSent($this->user->id, 'staff_invitation', User::class, $this->user->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.staff-invitation', [
                'subject' => $subject,
                'heading' => 'Staff Account Invitation',
                'userName' => $this->user->name,
                'roleName' => $roleName,
                'actionText' => 'Set Password & Access Portal',
                'actionUrl' => $this->invitationUrl,
            ])
            ->text('emails.staff-invitation-text', [
                'subject' => $subject,
                'heading' => 'Staff Account Invitation',
                'userName' => $this->user->name,
                'roleName' => $roleName,
                'actionText' => 'Set Password & Access Portal',
                'actionUrl' => $this->invitationUrl,
            ]);
    }

    public function failed(Throwable $e): void
    {
        $this->recordFailed($e, $this->user->id, 'staff_invitation', User::class, $this->user->id);
    }
}
