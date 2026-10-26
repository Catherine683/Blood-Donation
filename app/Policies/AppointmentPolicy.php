<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id || $user->isStaff();
    }

    /**
     * Determine whether the user can cancel the appointment.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }

    /**
     * Determine whether the user can reschedule the appointment.
     */
    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }
}
