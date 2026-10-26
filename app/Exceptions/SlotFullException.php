<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SlotFullException extends RuntimeException
{
    public function __construct(string $message = 'That slot has just been filled. Please choose another time.')
    {
        parent::__construct($message);
    }
}
