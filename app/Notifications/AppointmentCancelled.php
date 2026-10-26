<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\Concerns\LogsNotification;
use App\Support\DateHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AppointmentCancelled extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Appointment $appointment,
    ) {
        $this->dedupeKey = "cancellation:appointment:{$appointment->id}";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Determine if the notification should be sent at dispatch/run time.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! $notifiable instanceof User || ! $notifiable->is_active || ! $notifiable->hasVerifiedEmail()) {
            $this->recordSkipped($notifiable->id ?? 0, 'appointment_cancelled', 'User is inactive or email is unverified.', Appointment::class, $this->appointment->id);

            return false;
        }

        $this->appointment->refresh();

        if (! $this->appointment->isCancelled()) {
            $this->recordSkipped($notifiable->id, 'appointment_cancelled', 'Appointment is not in cancelled status.', Appointment::class, $this->appointment->id);

            return false;
        }

        return true;
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->appointment->slot;
        $drive = $slot?->drive;

        $datePart = DateHelper::formatDate($slot?->starts_at, 'D j M');
        $subject = "Appointment cancelled: {$datePart}";

        $dateTimeEat = DateHelper::formatDate($slot?->starts_at).' ('.DateHelper::formatRange($slot?->starts_at, $slot?->ends_at).')';

        $this->recordSent($notifiable->id, 'appointment_cancelled', Appointment::class, $this->appointment->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.appointment-cancelled', [
                'subject' => $subject,
                'heading' => 'Appointment Cancelled',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'dateTimeEat' => $dateTimeEat,
                'actionText' => 'Find Another Blood Drive',
                'actionUrl' => route('drives.index'),
            ])
            ->text('emails.appointment-cancelled-text', [
                'subject' => $subject,
                'heading' => 'Appointment Cancelled',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'dateTimeEat' => $dateTimeEat,
                'actionText' => 'Find Another Blood Drive',
                'actionUrl' => route('drives.index'),
            ]);
    }

    /**
     * Handle delivery failure after all retries are exhausted.
     */
    public function failed(Throwable $e): void
    {
        $this->recordFailed($e, (int) $this->appointment->user_id, 'appointment_cancelled', Appointment::class, $this->appointment->id);
    }
}
