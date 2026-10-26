<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class NotAllowedException extends RuntimeException
{
    public function __construct(string $message = 'You are not allowed to perform this booking action.')
    {
        parent::__construct($message);
    }
}
