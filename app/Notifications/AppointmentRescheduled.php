<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\Concerns\LogsNotification;
use App\Support\DateHelper;
use App\Support\IcsGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AppointmentRescheduled extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $newSlotId;

    public function __construct(
        public Appointment $appointment,
        public int $oldSlotId,
        ?int $newSlotId = null,
    ) {
        $this->newSlotId = $newSlotId ?? (int) $appointment->slot_id;
        $this->dedupeKey = "reschedule:appointment:{$appointment->id}:old:{$oldSlotId}:new:{$this->newSlotId}";
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
            $this->recordSkipped($notifiable->id ?? 0, 'appointment_rescheduled', 'User is inactive or email is unverified.', Appointment::class, $this->appointment->id);

            return false;
        }

        $this->appointment->refresh();

        if (! $this->appointment->isBooked()) {
            $this->recordSkipped($notifiable->id, 'appointment_rescheduled', 'Appointment is no longer booked.', Appointment::class, $this->appointment->id);

            return false;
        }

        if ($this->appointment->slot_id !== $this->newSlotId) {
            $this->recordSkipped($notifiable->id, 'appointment_rescheduled', 'Appointment was rescheduled again before send.', Appointment::class, $this->appointment->id);

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
        $timePart = DateHelper::formatTime($slot?->starts_at);
        $subject = "Updated appointment time: {$datePart}, {$timePart}";

        $dateTimeEat = DateHelper::formatDate($slot?->starts_at).' ('.DateHelper::formatRange($slot?->starts_at, $slot?->ends_at).')';

        $seq = max(1, IcsGenerator::resolveSequence($this->appointment));
        $icsContent = IcsGenerator::generate($this->appointment, sequence: $seq);

        $this->recordSent($notifiable->id, 'appointment_rescheduled', Appointment::class, $this->appointment->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.appointment-rescheduled', [
                'subject' => $subject,
                'heading' => 'Appointment Rescheduled',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'venue' => $drive?->venue ?? 'Kampala',
                'address' => $drive?->address,
                'dateTimeEat' => $dateTimeEat,
                'actionText' => 'View Updated Appointment',
                'actionUrl' => route('donor.appointments'),
                'rescheduleUrl' => $drive ? route('drives.show', $drive) : route('donor.appointments'),
            ])
            ->text('emails.appointment-rescheduled-text', [
                'subject' => $subject,
                'heading' => 'Appointment Rescheduled',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'venue' => $drive?->venue ?? 'Kampala',
                'address' => $drive?->address,
                'dateTimeEat' => $dateTimeEat,
                'actionText' => 'View Updated Appointment',
                'actionUrl' => route('donor.appointments'),
                'rescheduleUrl' => $drive ? route('drives.show', $drive) : route('donor.appointments'),
            ])
            ->attachData($icsContent, 'donorlink-appointment.ics', [
                'mime' => 'text/calendar; charset=UTF-8; method=PUBLISH',
            ]);
    }

    /**
     * Handle delivery failure after all retries are exhausted.
     */
    public function failed(Throwable $e): void
    {
        $this->recordFailed($e, (int) $this->appointment->user_id, 'appointment_rescheduled', Appointment::class, $this->appointment->id);
    }
}
