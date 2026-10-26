<?php

declare(strict_types=1);

namespace App\Enums;

enum DriveStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';

    /**
     * Human-friendly label for UI presentation.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Closed => 'Closed',
        };
    }

    /**
     * Semantic badge color variant from design system.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Closed => 'danger',
        };
    }
}
