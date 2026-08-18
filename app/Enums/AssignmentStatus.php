<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum AssignmentStatus: string
{
    use HasEnumHelpers;

    case Pending = 'pending';
    case Contacted = 'contacted';
    case Skipped = 'skipped';
    case Snoozed = 'snoozed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Contacted => 'Contacted',
            self::Skipped => 'Skipped',
            self::Snoozed => 'Snoozed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'sky',
            self::Contacted => 'emerald',
            self::Skipped => 'slate',
            self::Snoozed => 'amber',
        };
    }

    /**
     * Statuses that still need action from the user.
     *
     * @return array<int, self>
     */
    public static function outstanding(): array
    {
        return [self::Pending, self::Snoozed];
    }

    /**
     * Whether the assignment still needs action from the user.
     */
    public function isOutstanding(): bool
    {
        return $this->in(...self::outstanding());
    }
}
