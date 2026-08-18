<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum Priority: string
{
    use HasEnumHelpers;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'slate',
            self::Medium => 'sky',
            self::High => 'amber',
            self::Urgent => 'rose',
        };
    }

    /**
     * Points contributed to the outreach ranking score.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Medium => 8,
            self::High => 16,
            self::Urgent => 24,
        };
    }
}
