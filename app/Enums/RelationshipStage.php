<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum RelationshipStage: string
{
    use HasEnumHelpers;

    case Stranger = 'stranger';
    case Introduced = 'introduced';
    case Conversing = 'conversing';
    case Engaged = 'engaged';
    case Client = 'client';
    case Dormant = 'dormant';

    public function label(): string
    {
        return match ($this) {
            self::Stranger => 'Stranger',
            self::Introduced => 'Introduced',
            self::Conversing => 'In conversation',
            self::Engaged => 'Engaged',
            self::Client => 'Client',
            self::Dormant => 'Dormant',
        };
    }

    /**
     * Points contributed to the lead score for previous engagement.
     */
    public function scoreWeight(): int
    {
        return match ($this) {
            self::Stranger => 0,
            self::Introduced => 4,
            self::Conversing => 8,
            self::Engaged => 12,
            self::Client => 15,
            self::Dormant => 3,
        };
    }
}
