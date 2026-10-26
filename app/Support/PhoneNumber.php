<?php

declare(strict_types=1);

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise a raw Ugandan phone number string to E.164 format (+256...).
     * Returns null if the number cannot be parsed into valid format.
     */
    public static function normalise(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Strip spaces, dashes, brackets, and any non-digit except leading '+'
        $trimmed = trim($value);
        $hasLeadingPlus = str_starts_with($trimmed, '+');
        $digitsOnly = preg_replace('/[^0-9]/', '', $trimmed);

        if (! is_string($digitsOnly) || $digitsOnly === '') {
            return null;
        }

        $e164 = null;

        if ($hasLeadingPlus && str_starts_with($digitsOnly, '256') && strlen($digitsOnly) === 12) {
            $e164 = '+'.$digitsOnly;
        } elseif (str_starts_with($digitsOnly, '256') && strlen($digitsOnly) === 12) {
            $e164 = '+'.$digitsOnly;
        } elseif (str_starts_with($digitsOnly, '0') && strlen($digitsOnly) === 10) {
            $e164 = '+256'.substr($digitsOnly, 1);
        } elseif (! str_starts_with($digitsOnly, '0') && strlen($digitsOnly) === 9) {
            $e164 = '+256'.$digitsOnly;
        }

        if ($e164 !== null && preg_match('/^\+256[0-9]{9}$/', $e164) === 1) {
            return $e164;
        }

        return null;
    }

    /**
     * Check if a raw string is a valid Ugandan phone number.
     */
    public static function isValid(?string $value): bool
    {
        return self::normalise($value) !== null;
    }

    /**
     * Format an E.164 or raw Ugandan phone number for human-readable display.
     * Example: '+256772123456' -> '+256 772 123 456'
     */
    public static function format(?string $value): string
    {
        $normalised = self::normalise($value) ?? $value;

        if ($normalised === null || $normalised === '') {
            return '';
        }

        if (preg_match('/^\+256([0-9]{3})([0-9]{3})([0-9]{3})$/', $normalised, $matches) === 1) {
            return sprintf('+256 %s %s %s', $matches[1], $matches[2], $matches[3]);
        }

        return $normalised;
    }

    /**
     * Friendly validation error message.
     */
    public static function errorMessage(): string
    {
        return 'Enter a Ugandan phone number, for example 0772 123 456';
    }
}
