<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class AlreadyBookedException extends RuntimeException
{
    public function __construct(string $message = 'You already have an appointment booked for this donation drive.')
    {
        parent::__construct($message);
    }
}
