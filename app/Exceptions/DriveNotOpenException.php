<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class DriveNotOpenException extends RuntimeException
{
    public function __construct(string $message = 'This blood drive is not open for public booking.')
    {
        parent::__construct($message);
    }
}
