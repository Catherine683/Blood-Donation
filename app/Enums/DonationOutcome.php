<?php

declare(strict_types=1);

namespace App\Enums;

enum DonationOutcome: string
{
    case Donated = 'donated';
    case Deferred = 'deferred';

    /**
     * Staff-facing outcome label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Donated => 'Donated',
            self::Deferred => 'Did not donate',
        };
    }

    /**
     * Donor-facing outcome label.
     * Note: "deferred" is never shown to donors.
     */
    public function donorFacingLabel(): string
    {
        return match ($this) {
            self::Donated => 'Donated',
            self::Deferred => 'Attended (no donation recorded)',
        };
    }

    /**
     * Semantic badge color variant from design system.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Donated => 'success',
            self::Deferred => 'warning',
        };
    }
}
