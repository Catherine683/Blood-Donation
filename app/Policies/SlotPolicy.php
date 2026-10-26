<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Slot;
use App\Models\User;

class SlotPolicy
{
    /**
     * Determine whether the user can create slots.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can update the slot.
     */
    public function update(User $user, Slot $slot): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can delete the slot.
     */
    public function delete(User $user, Slot $slot): bool
    {
        return $user->isStaff();
    }

    /**
     * General manage check for staff operations.
     */
    public function manage(User $user): bool
    {
        return $user->isStaff();
    }
}
