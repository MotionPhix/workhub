<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum OrganizationType: string
{
    use HasEnumHelpers;

    case Ngo = 'ngo';
    case InternationalNgo = 'international_ngo';
    case Business = 'business';
    case Startup = 'startup';
    case Agency = 'agency';
    case Government = 'government';
    case Parastatal = 'parastatal';
    case Education = 'education';
    case Health = 'health';
    case Financial = 'financial';
    case Religious = 'religious';
    case Cooperative = 'cooperative';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ngo => 'NGO',
            self::InternationalNgo => 'International NGO',
            self::Business => 'Business',
            self::Startup => 'Startup',
            self::Agency => 'Agency',
            self::Government => 'Government',
            self::Parastatal => 'Parastatal',
            self::Education => 'Education',
            self::Health => 'Health',
            self::Financial => 'Financial Services',
            self::Religious => 'Religious Organisation',
            self::Cooperative => 'Cooperative',
            self::Other => 'Other',
        };
    }

    /**
     * Points contributed to the lead score, reflecting how reliably this kind
     * of organisation commissions creative and digital work.
     */
    public function scoreWeight(): int
    {
        return match ($this) {
            self::InternationalNgo => 15,
            self::Ngo, self::Financial => 13,
            self::Business, self::Health => 11,
            self::Education, self::Parastatal => 10,
            self::Startup => 9,
            self::Government, self::Cooperative => 7,
            self::Agency => 6,
            self::Religious => 5,
            self::Other => 4,
        };
    }
}
