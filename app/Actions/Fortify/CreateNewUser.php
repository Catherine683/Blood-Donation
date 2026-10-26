<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Actions\Donor\RegisterDonor;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        return (new RegisterDonor)->register($input);
    }
}
