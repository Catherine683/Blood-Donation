<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';

    /**
     * Human-friendly label for presentation.
     */
    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Booked',
            self::Cancelled => 'Cancelled',
            self::Attended => 'Attended',
            self::NoShow => 'No Show',
        };
    }

    /**
     * Semantic badge color variant from design system.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Booked => 'success',
            self::Cancelled => 'danger',
            self::Attended => 'info',
            self::NoShow => 'neutral',
        };
    }
}
