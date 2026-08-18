<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum TemplateCategory: string
{
    use HasEnumHelpers;

    case ColdOutreach = 'cold_outreach';
    case Introduction = 'introduction';
    case FollowUp = 'follow_up';
    case ProposalIntroduction = 'proposal_introduction';
    case Networking = 'networking';
    case Partnership = 'partnership';
    case Freelance = 'freelance';
    case JobOpportunity = 'job_opportunity';
    case NgoOutreach = 'ngo_outreach';
    case AgencyOutreach = 'agency_outreach';
    case ThankYou = 'thank_you';
    case ReEngagement = 're_engagement';

    public function label(): string
    {
        return match ($this) {
            self::ColdOutreach => 'Cold Outreach',
            self::Introduction => 'Introduction',
            self::FollowUp => 'Follow-up',
            self::ProposalIntroduction => 'Proposal Introduction',
            self::Networking => 'Networking',
            self::Partnership => 'Partnership',
            self::Freelance => 'Freelance',
            self::JobOpportunity => 'Job Opportunity',
            self::NgoOutreach => 'NGO Outreach',
            self::AgencyOutreach => 'Agency Outreach',
            self::ThankYou => 'Thank You',
            self::ReEngagement => 'Re-engagement',
        };
    }
}
