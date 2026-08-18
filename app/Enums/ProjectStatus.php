<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum ProjectStatus: string
{
    use HasEnumHelpers;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In progress',
            self::Review => 'In review',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Planned => 'sky',
            self::InProgress => 'indigo',
            self::Review => 'violet',
            self::Completed => 'emerald',
            self::Cancelled => 'slate',
        };
    }
}
