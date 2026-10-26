<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Drive;
use App\Models\User;

class DrivePolicy
{
    /**
     * Determine whether the user can view the drive index (staff screen).
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view the drive.
     */
    public function view(?User $user, Drive $drive): bool
    {
        if ($drive->isPublished() || $drive->isClosed()) {
            return true;
        }

        return $user !== null && $user->isStaff();
    }

    /**
     * Determine whether the user can create drives.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can update the drive.
     */
    public function update(User $user, Drive $drive): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can delete the drive.
     */
    public function delete(User $user, Drive $drive): bool
    {
        return $user->isStaff() && $drive->isDraft();
    }

    /**
     * Determine whether the user can publish the drive.
     */
    public function publish(User $user, Drive $drive): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can close the drive.
     */
    public function close(User $user, Drive $drive): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can reopen the drive.
     */
    public function reopen(User $user, Drive $drive): bool
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

    /**
     * Determine whether the user can manage the attendance roster.
     */
    public function manageRoster(User $user, Drive $drive): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can close out the drive.
     */
    public function closeOut(User $user, Drive $drive): bool
    {
        return $user->isStaff();
    }
}
