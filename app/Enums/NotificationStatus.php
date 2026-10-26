<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /**
     * Human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Skipped => 'Skipped',
        };
    }

    /**
     * Design system badge variant.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Queued => 'neutral',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Skipped => 'warning',
        };
    }
}
