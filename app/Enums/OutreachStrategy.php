<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum OutreachStrategy: string
{
    use HasEnumHelpers;

    case Cold = 'cold';
    case Warm = 'warm';
    case ReEngagement = 're_engagement';
    case Referral = 'referral';
    case Upsell = 'upsell';

    public function label(): string
    {
        return match ($this) {
            self::Cold => 'Cold Outreach',
            self::Warm => 'Warm Outreach',
            self::ReEngagement => 'Re-engagement',
            self::Referral => 'Referral',
            self::Upsell => 'Existing Client Upsell',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cold => 'Completely new prospects who have never heard from you.',
            self::Warm => 'People you already know or have met before.',
            self::ReEngagement => 'Previous prospects who went quiet.',
            self::Referral => 'People referred to you by an existing contact.',
            self::Upsell => 'Offer another service to an existing client.',
        };
    }

    /**
     * The email template category that best fits this strategy.
     */
    public function defaultTemplateCategory(): TemplateCategory
    {
        return match ($this) {
            self::Cold => TemplateCategory::ColdOutreach,
            self::Warm => TemplateCategory::Introduction,
            self::ReEngagement => TemplateCategory::ReEngagement,
            self::Referral => TemplateCategory::Networking,
            self::Upsell => TemplateCategory::Partnership,
        };
    }
}
