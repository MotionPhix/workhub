<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum CompanySize: string
{
    use HasEnumHelpers;

    case Solo = 'solo';
    case Micro = 'micro';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Solo => 'Solo (1)',
            self::Micro => 'Micro (2-10)',
            self::Small => 'Small (11-50)',
            self::Medium => 'Medium (51-200)',
            self::Large => 'Large (201-1000)',
            self::Enterprise => 'Enterprise (1000+)',
        };
    }

    /**
     * Points contributed to the lead score. Mid-sized organisations are the
     * sweet spot for a solo creative professional: real budgets, no
     * procurement bureaucracy.
     */
    public function scoreWeight(): int
    {
        return match ($this) {
            self::Solo => 2,
            self::Micro => 6,
            self::Small => 12,
            self::Medium => 15,
            self::Large => 11,
            self::Enterprise => 7,
        };
    }
}
