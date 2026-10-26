<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\NotificationStatus;
use App\Events\AppointmentRescheduled as AppointmentRescheduledEvent;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Notifications\AppointmentRescheduled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Queue\InteractsWithQueue;

class SendAppointmentRescheduledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function handle(AppointmentRescheduledEvent $event): void
    {
        $appointment = $event->appointment;
        $appointment->loadMissing('user');

        $user = $appointment->user;
        if (! $user) {
            return;
        }

        $oldSlotId = $event->oldSlotId;
        $newSlotId = $event->newSlotId;
        $dedupeKey = "reschedule:appointment:{$appointment->id}:old:{$oldSlotId}:new:{$newSlotId}";

        try {
            NotificationLog::create([
                'user_id' => $user->id,
                'type' => 'appointment_rescheduled',
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

        $user->notify(new AppointmentRescheduled($appointment, $oldSlotId, $newSlotId));
    }
}
