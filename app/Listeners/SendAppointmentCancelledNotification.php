<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\NotificationStatus;
use App\Events\AppointmentCancelled as AppointmentCancelledEvent;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Notifications\AppointmentCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Queue\InteractsWithQueue;

class SendAppointmentCancelledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function handle(AppointmentCancelledEvent $event): void
    {
        $appointment = $event->appointment;
        $appointment->loadMissing('user');

        $user = $appointment->user;
        if (! $user) {
            return;
        }

        $dedupeKey = "cancellation:appointment:{$appointment->id}";

        try {
            NotificationLog::create([
                'user_id' => $user->id,
                'type' => 'appointment_cancelled',
                'channel' => 'mail',
                'status' => NotificationStatus::Queued,
                'related_type' => Appointment::class,
                'related_id' => $appointment->id,
                'dedupe_key' => $dedupeKey,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already created or processed
            return;
        }

        $user->notify(new AppointmentCancelled($appointment));
    }
}
