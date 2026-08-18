<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum LeadSource: string
{
    use HasEnumHelpers;

    case Referral = 'referral';
    case Website = 'website';
    case LinkedIn = 'linkedin';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Directory = 'directory';
    case Event = 'event';
    case ColdResearch = 'cold_research';
    case WordOfMouth = 'word_of_mouth';
    case ExistingClient = 'existing_client';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Referral => 'Referral',
            self::Website => 'Website enquiry',
            self::LinkedIn => 'LinkedIn',
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Directory => 'Directory / listing',
            self::Event => 'Event or conference',
            self::ColdResearch => 'Cold research',
            self::WordOfMouth => 'Word of mouth',
            self::ExistingClient => 'Existing client',
            self::Other => 'Other',
        };
    }

    /**
     * Points contributed to the lead score. Warm sources convert far better
     * than names scraped from a directory.
     */
    public function scoreWeight(): int
    {
        return match ($this) {
            self::Referral, self::ExistingClient => 12,
            self::WordOfMouth => 10,
            self::Website => 9,
            self::Event => 8,
            self::LinkedIn => 6,
            self::Facebook, self::Instagram => 4,
            self::Directory => 3,
            self::ColdResearch => 2,
            self::Other => 1,
        };
    }
}
