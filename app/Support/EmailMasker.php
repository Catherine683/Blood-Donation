<?php

declare(strict_types=1);

namespace App\Support;

class EmailMasker
{
    /**
     * Mask an email address for privacy (e.g. j***@example.ug).
     */
    public static function mask(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        $firstChar = mb_substr($local, 0, 1);

        return $firstChar.'***@'.$domain;
    }
}
