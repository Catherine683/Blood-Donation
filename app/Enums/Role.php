<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Donor = 'donor';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Donor => 'Donor',
            self::Staff => 'Staff',
            self::Admin => 'Administrator',
        };
    }
}
