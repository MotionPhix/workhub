<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum CycleStatus: string
{
    use HasEnumHelpers;

    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Planned => 'sky',
            self::Active => 'emerald',
            self::Completed => 'indigo',
            self::Archived => 'slate',
        };
    }
}
