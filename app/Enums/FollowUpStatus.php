<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum FollowUpStatus: string
{
    use HasEnumHelpers;

    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Completed => 'emerald',
            self::Cancelled => 'slate',
        };
    }
}
