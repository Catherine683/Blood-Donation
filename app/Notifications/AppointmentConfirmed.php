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

class AppointmentConfirmed extends Notification implements ShouldQueue
{
    use LogsNotification, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $slotId;

    public function __construct(
        public Appointment $appointment,
    ) {
        $this->slotId = (int) $appointment->slot_id;
        $this->dedupeKey = "confirmation:appointment:{$appointment->id}:slot:{$this->slotId}";
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
            $this->recordSkipped($notifiable->id ?? 0, 'appointment_confirmed', 'User is inactive or email is unverified.', Appointment::class, $this->appointment->id);

            return false;
        }

        $this->appointment->refresh();

        if (! $this->appointment->isBooked()) {
            $this->recordSkipped($notifiable->id, 'appointment_confirmed', 'Appointment is no longer booked.', Appointment::class, $this->appointment->id);

            return false;
        }

        if ($this->appointment->slot_id !== $this->slotId) {
            $this->recordSkipped($notifiable->id, 'appointment_confirmed', 'Appointment slot was modified before send.', Appointment::class, $this->appointment->id);

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
        $subject = "Your blood donation appointment: {$datePart}, {$timePart}";

        $dateTimeEat = DateHelper::formatDate($slot?->starts_at).' ('.DateHelper::formatRange($slot?->starts_at, $slot?->ends_at).')';

        $icsContent = IcsGenerator::generate($this->appointment, sequence: 0);

        $this->recordSent($notifiable->id, 'appointment_confirmed', Appointment::class, $this->appointment->id);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.appointment-confirmed', [
                'subject' => $subject,
                'heading' => 'Appointment Confirmed',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'venue' => $drive?->venue ?? 'Kampala',
                'address' => $drive?->address,
                'dateTimeEat' => $dateTimeEat,
                'actionText' => 'View Appointment',
                'actionUrl' => route('donor.appointments'),
                'rescheduleUrl' => $drive ? route('drives.show', $drive) : route('donor.appointments'),
            ])
            ->text('emails.appointment-confirmed-text', [
                'subject' => $subject,
                'heading' => 'Appointment Confirmed',
                'donorName' => $notifiable->name,
                'driveTitle' => $drive?->title ?? 'UBTS Blood Drive',
                'venue' => $drive?->venue ?? 'Kampala',
                'address' => $drive?->address,
                'dateTimeEat' => $dateTimeEat,
                'actionText' => 'View Appointment',
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
        $this->recordFailed($e, (int) $this->appointment->user_id, 'appointment_confirmed', Appointment::class, $this->appointment->id);
    }
}
