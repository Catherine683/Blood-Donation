<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Collection;
use RuntimeException;

class CloseOutBlockedException extends RuntimeException
{
    /**
     * @param  Collection<int, mixed>  $unresolvedDonors
     */
    public function __construct(
        public Collection $unresolvedDonors,
        string $message = 'Cannot close out drive while checked-in donors have no recorded outcome.'
    ) {
        parent::__construct($message);
    }
}
