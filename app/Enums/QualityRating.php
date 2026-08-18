<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

/**
 * Used to rate a prospect's existing website and branding during research.
 *
 * A weak rating is an opportunity signal, not a negative: it means there is
 * obvious work to pitch.
 */
enum QualityRating: string
{
    use HasEnumHelpers;

    case None = 'none';
    case Poor = 'poor';
    case Dated = 'dated';
    case Adequate = 'adequate';
    case Strong = 'strong';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None / non-existent',
            self::Poor => 'Poor',
            self::Dated => 'Dated',
            self::Adequate => 'Adequate',
            self::Strong => 'Strong',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::None, self::Poor => 'rose',
            self::Dated => 'amber',
            self::Adequate => 'sky',
            self::Strong => 'emerald',
        };
    }

    /**
     * Points contributed to the lead score. The worse the current asset, the
     * bigger the opportunity to sell a replacement.
     */
    public function opportunityWeight(): int
    {
        return match ($this) {
            self::None => 10,
            self::Poor => 9,
            self::Dated => 7,
            self::Adequate => 3,
            self::Strong => 1,
        };
    }
}
