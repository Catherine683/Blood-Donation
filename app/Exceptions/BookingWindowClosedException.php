<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class BookingWindowClosedException extends RuntimeException
{
    public function __construct(string $message = 'Booking for this time slot has closed.')
    {
        parent::__construct($message);
    }
}
