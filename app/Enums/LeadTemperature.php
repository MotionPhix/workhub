<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum LeadTemperature: string
{
    use HasEnumHelpers;

    case Hot = 'hot';
    case Warm = 'warm';
    case Cold = 'cold';

    public function label(): string
    {
        return match ($this) {
            self::Hot => 'Hot',
            self::Warm => 'Warm',
            self::Cold => 'Cold',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Hot => 'rose',
            self::Warm => 'amber',
            self::Cold => 'sky',
        };
    }

    /**
     * Derive the temperature label from a 0-100 lead score.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 70 => self::Hot,
            $score >= 40 => self::Warm,
            default => self::Cold,
        };
    }
}
