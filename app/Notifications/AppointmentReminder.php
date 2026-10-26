<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\Concerns\LogsNotification;
use App\Support\DateHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $slotId;

    public function __construct(
        public Appointment $appointment,
        public string $variant = '24h',
    ) {
        $this->slotId = (int) $appointment->slot_id;
        $this->dedupeKey = "reminder_{$variant}:appointment:{$appointment->id}:slot:{$this->slotId}";
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
        $logType = "reminder_{$this->variant}";

        if (! $notifiable instanceof User || ! $notifiable->is_active || ! $notifiable->hasVerifiedEmail()) {
            $this->recordSkipped($notifiable->id ?? 0, $logType, 'User is inactive or email is unverified.', Appointment::class, $this->appointment->id);

            return false;
        }

        $this->appointment->refresh();

        if (! $this->appointment->isBooked()) {
            $this->recordSkipped($notifiable->id, $logType, 'Appointment is no longer booked.', Appointment::class, $this->appointment->id);

            return false;
        }

        if ($this->appointment->slot_id !== $this->slotId) {
            $this->recordSkipped($notifiable->id, $logType, 'Appointment slot was modified before reminder dispatch.', Appointment::class, $this->appointment->id);

            return false;
        }

        // 3-hour skip rule: Do not send a reminder if a confirmation or reschedule email for the same slot was sent less than 3 hours earlier
        $recentSendExists = NotificationLog::query()
            ->where('related_type', Appointment::class)
            ->where('related_id', $this->appointment->id)
            ->whereIn('type', ['appointment_confirmed', 'appointment_rescheduled'])
            ->where('status', NotificationStatus::Sent->value)
            ->where('sent_at', '>=', Carbon::now()->subHours(3))
            ->exists();

        if ($recentSendExists) {
            $this->recordSkipped($notifiable->id, $logType, 'Confirmation or reschedule sent less than 3 hours ago.', Appointment::class, $this->appointment->id);

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
        $logType = "reminder_{$this->variant}";

        $timePart = DateHelper::formatTime($slot?->starts_at);

        if ($this->variant === '2h') {
            $subject = "Reminder: Your blood donation appointment in 2 hours, {$timePart}";
            $timeWindowText = 'in approximately 2 hours';
        } else {
            $subject = "Reminder: Your blood donation appointment tomorrow, {$timePart}";
            $timeWindowText = 'tomorrow';
        }

        $dateTimeEat = DateHelper::formatDate($slot?->starts_at).' ('.DateHelper::formatRange($slot?->starts_at, $slot?->ends_at).')';

        $this->recordSent($notifiable->id, $logType, Appointment::class, $this->appointment->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.appointment-reminder', [
                'subject' => $subject,
                'heading' => 'Donation Appointment Reminder',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'venue' => $drive?->venue ?? 'Kampala',
                'address' => $drive?->address,
                'dateTimeEat' => $dateTimeEat,
                'timeWindowText' => $timeWindowText,
                'actionText' => 'View Appointment',
                'actionUrl' => route('donor.appointments'),
                'rescheduleUrl' => $drive ? route('drives.show', $drive) : route('donor.appointments'),
            ])
            ->text('emails.appointment-reminder-text', [
                'subject' => $subject,
                'heading' => 'Donation Appointment Reminder',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'venue' => $drive?->venue ?? 'Kampala',
                'address' => $drive?->address,
                'dateTimeEat' => $dateTimeEat,
                'timeWindowText' => $timeWindowText,
                'actionText' => 'View Appointment',
                'actionUrl' => route('donor.appointments'),
                'rescheduleUrl' => $drive ? route('drives.show', $drive) : route('donor.appointments'),
            ]);
    }

    /**
     * Handle delivery failure after all retries are exhausted.
     */
    public function failed(Throwable $e): void
    {
        $this->recordFailed($e, (int) $this->appointment->user_id, "reminder_{$this->variant}", Appointment::class, $this->appointment->id);
    }
}
