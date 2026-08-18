<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

/**
 * Every meaningful action in the CRM is written to the activity log using one
 * of these types, which drives the prospect timeline.
 */
enum ActivityType: string
{
    use HasEnumHelpers;

    case ContactCreated = 'contact_created';
    case ContactUpdated = 'contact_updated';
    case ContactImported = 'contact_imported';
    case StatusChanged = 'status_changed';
    case NoteAdded = 'note_added';
    case ResearchUpdated = 'research_updated';
    case CompanyCreated = 'company_created';
    case CompanyUpdated = 'company_updated';
    case AssignedToCycle = 'assigned_to_cycle';
    case OutreachSkipped = 'outreach_skipped';
    case OutreachSnoozed = 'outreach_snoozed';
    case EmailDrafted = 'email_drafted';
    case EmailSent = 'email_sent';
    case EmailFailed = 'email_failed';
    case EmailOpened = 'email_opened';
    case EmailReplied = 'email_replied';
    case ReplyLogged = 'reply_logged';
    case MarkedContacted = 'marked_contacted';
    case FollowUpCreated = 'follow_up_created';
    case FollowUpCompleted = 'follow_up_completed';
    case FollowUpCancelled = 'follow_up_cancelled';
    case ProposalCreated = 'proposal_created';
    case ProposalVersionCreated = 'proposal_version_created';
    case ProposalSent = 'proposal_sent';
    case ProposalViewed = 'proposal_viewed';
    case ProposalAccepted = 'proposal_accepted';
    case ProposalRejected = 'proposal_rejected';
    case ProposalExpired = 'proposal_expired';
    case OpportunityCreated = 'opportunity_created';
    case OpportunityStageChanged = 'opportunity_stage_changed';
    case DealWon = 'deal_won';
    case DealLost = 'deal_lost';
    case ClientConverted = 'client_converted';
    case AiDraftGenerated = 'ai_draft_generated';

    public function label(): string
    {
        return match ($this) {
            self::ContactCreated => 'Prospect created',
            self::ContactUpdated => 'Prospect updated',
            self::ContactImported => 'Prospect imported',
            self::StatusChanged => 'Status changed',
            self::NoteAdded => 'Note added',
            self::ResearchUpdated => 'Research updated',
            self::CompanyCreated => 'Company created',
            self::CompanyUpdated => 'Company updated',
            self::AssignedToCycle => 'Assigned to outreach cycle',
            self::OutreachSkipped => 'Outreach skipped',
            self::OutreachSnoozed => 'Outreach snoozed',
            self::EmailDrafted => 'Email drafted',
            self::EmailSent => 'Email sent',
            self::EmailFailed => 'Email failed',
            self::EmailOpened => 'Email opened',
            self::EmailReplied => 'Email replied',
            self::ReplyLogged => 'Reply logged',
            self::MarkedContacted => 'Marked as contacted',
            self::FollowUpCreated => 'Follow-up scheduled',
            self::FollowUpCompleted => 'Follow-up completed',
            self::FollowUpCancelled => 'Follow-up cancelled',
            self::ProposalCreated => 'Proposal created',
            self::ProposalVersionCreated => 'Proposal version created',
            self::ProposalSent => 'Proposal sent',
            self::ProposalViewed => 'Proposal viewed',
            self::ProposalAccepted => 'Proposal accepted',
            self::ProposalRejected => 'Proposal rejected',
            self::ProposalExpired => 'Proposal expired',
            self::OpportunityCreated => 'Opportunity created',
            self::OpportunityStageChanged => 'Opportunity stage changed',
            self::DealWon => 'Deal won',
            self::DealLost => 'Deal lost',
            self::ClientConverted => 'Converted to client',
            self::AiDraftGenerated => 'AI draft generated',
        };
    }

    /**
     * The lucide icon name rendered next to the entry on the timeline.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ContactCreated, self::ContactImported => 'user-plus',
            self::ContactUpdated, self::CompanyUpdated, self::ResearchUpdated => 'pencil',
            self::StatusChanged, self::OpportunityStageChanged => 'arrow-right-left',
            self::NoteAdded => 'sticky-note',
            self::CompanyCreated => 'building-2',
            self::AssignedToCycle => 'calendar-plus',
            self::OutreachSkipped => 'skip-forward',
            self::OutreachSnoozed => 'alarm-clock',
            self::EmailDrafted => 'file-pen',
            self::EmailSent, self::MarkedContacted => 'send',
            self::EmailFailed => 'mail-warning',
            self::EmailOpened => 'mail-open',
            self::EmailReplied, self::ReplyLogged => 'reply',
            self::FollowUpCreated => 'clock',
            self::FollowUpCompleted => 'check-check',
            self::FollowUpCancelled => 'x',
            self::ProposalCreated, self::ProposalVersionCreated => 'file-text',
            self::ProposalSent => 'file-up',
            self::ProposalViewed => 'eye',
            self::ProposalAccepted => 'file-check',
            self::ProposalRejected => 'file-x',
            self::ProposalExpired => 'file-clock',
            self::OpportunityCreated => 'target',
            self::DealWon, self::ClientConverted => 'trophy',
            self::DealLost => 'thumbs-down',
            self::AiDraftGenerated => 'sparkles',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::EmailSent, self::MarkedContacted, self::ProposalSent => 'indigo',
            self::EmailReplied, self::ReplyLogged, self::ProposalAccepted, self::DealWon, self::ClientConverted => 'emerald',
            self::EmailFailed, self::ProposalRejected, self::DealLost => 'rose',
            self::FollowUpCreated, self::OutreachSnoozed, self::ProposalExpired => 'amber',
            self::AiDraftGenerated => 'violet',
            default => 'slate',
        };
    }
}
