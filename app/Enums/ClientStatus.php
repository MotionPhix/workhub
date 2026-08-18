<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum ClientStatus: string
{
    use HasEnumHelpers;

    case Active = 'active';
    case Paused = 'paused';
    case Former = 'former';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Former => 'Former',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Paused => 'amber',
            self::Former => 'slate',
        };
    }
}
