<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentRescheduled
{
    use Dispatchable, SerializesModels;

    public int $newSlotId;

    public function __construct(
        public Appointment $appointment,
        public int $oldSlotId,
        ?int $newSlotId = null,
    ) {
        $this->newSlotId = $newSlotId ?? (int) $appointment->slot_id;
    }
}
