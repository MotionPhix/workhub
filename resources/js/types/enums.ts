export type ContactStatus =
    | 'new'
    | 'researching'
    | 'ready_to_contact'
    | 'contacted'
    | 'replied'
    | 'interested'
    | 'follow_up'
    | 'proposal_requested'
    | 'proposal_sent'
    | 'negotiating'
    | 'won'
    | 'lost'
    | 'not_interested'
    | 'do_not_contact';

export type LeadTemperature = 'hot' | 'warm' | 'cold';

export type Priority = 'low' | 'medium' | 'high' | 'urgent';

export type RelationshipStage =
    | 'stranger'
    | 'introduced'
    | 'conversing'
    | 'engaged'
    | 'client'
    | 'dormant';

export type LeadSource =
    | 'referral'
    | 'website'
    | 'linkedin'
    | 'facebook'
    | 'instagram'
    | 'directory'
    | 'event'
    | 'cold_research'
    | 'word_of_mouth'
    | 'existing_client'
    | 'other';

export type CompanySize =
    | 'solo'
    | 'micro'
    | 'small'
    | 'medium'
    | 'large'
    | 'enterprise';

export type OrganizationType =
    | 'ngo'
    | 'international_ngo'
    | 'business'
    | 'startup'
    | 'agency'
    | 'government'
    | 'parastatal'
    | 'education'
    | 'health'
    | 'financial'
    | 'religious'
    | 'cooperative'
    | 'other';

export type AssignmentStatus = 'pending' | 'contacted' | 'skipped' | 'snoozed';

export type OutreachStrategy =
    | 'cold'
    | 'warm'
    | 're_engagement'
    | 'referral'
    | 'upsell';

export type CycleStatus = 'planned' | 'active' | 'completed' | 'archived';

export type OpportunityStage =
    | 'new'
    | 'contacted'
    | 'interested'
    | 'proposal'
    | 'negotiation'
    | 'won'
    | 'lost';

export type ProposalStatus =
    | 'draft'
    | 'internal_review'
    | 'sent'
    | 'viewed'
    | 'accepted'
    | 'rejected'
    | 'expired';

export type TemplateStatus = 'draft' | 'active' | 'archived';

export type TemplateCategory =
    | 'cold_outreach'
    | 'introduction'
    | 'follow_up'
    | 'proposal_introduction'
    | 'networking'
    | 'partnership'
    | 'freelance'
    | 'job_opportunity'
    | 'ngo_outreach'
    | 'agency_outreach'
    | 'thank_you'
    | 're_engagement';

export type EmailDirection = 'outbound' | 'inbound';

export type EmailStatus =
    | 'draft'
    | 'queued'
    | 'sent'
    | 'delivered'
    | 'opened'
    | 'replied'
    | 'failed'
    | 'bounced';

export type EmailEventType =
    | 'created'
    | 'queued'
    | 'sent'
    | 'delivered'
    | 'opened'
    | 'clicked'
    | 'replied'
    | 'bounced'
    | 'failed';

export type FollowUpStatus = 'pending' | 'completed' | 'cancelled';

export type FollowUpType =
    | 'email'
    | 'call'
    | 'whatsapp'
    | 'meeting'
    | 'linkedin'
    | 'other';

export type ClientStatus = 'active' | 'paused' | 'former';

export type ProjectStatus =
    | 'planned'
    | 'in_progress'
    | 'review'
    | 'completed'
    | 'cancelled';

export type QualityRating = 'none' | 'poor' | 'dated' | 'adequate' | 'strong';

export type ActivityType =
    | 'contact_created'
    | 'contact_updated'
    | 'contact_imported'
    | 'status_changed'
    | 'note_added'
    | 'research_updated'
    | 'company_created'
    | 'company_updated'
    | 'assigned_to_cycle'
    | 'outreach_skipped'
    | 'outreach_snoozed'
    | 'email_drafted'
    | 'email_sent'
    | 'email_failed'
    | 'email_opened'
    | 'email_replied'
    | 'reply_logged'
    | 'marked_contacted'
    | 'follow_up_created'
    | 'follow_up_completed'
    | 'follow_up_cancelled'
    | 'proposal_created'
    | 'proposal_version_created'
    | 'proposal_sent'
    | 'proposal_viewed'
    | 'proposal_accepted'
    | 'proposal_rejected'
    | 'proposal_expired'
    | 'opportunity_created'
    | 'opportunity_stage_changed'
    | 'deal_won'
    | 'deal_lost'
    | 'client_converted'
    | 'ai_draft_generated';
