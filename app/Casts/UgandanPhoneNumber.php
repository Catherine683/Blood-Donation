<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
class UgandanPhoneNumber implements CastsAttributes
{
    /**
     * Cast the given value from storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    /**
     * Prepare the given value for storage (normalising to E.164).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return PhoneNumber::normalise((string) $value) ?? (string) $value;
    }
}
