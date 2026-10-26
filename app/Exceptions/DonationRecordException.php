<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class DonationRecordException extends RuntimeException
{
    public function __construct(string $message = 'Unable to record donation.')
    {
        parent::__construct($message);
    }
}
