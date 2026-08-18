<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum ContactStatus: string
{
    use HasEnumHelpers;

    case New = 'new';
    case Researching = 'researching';
    case ReadyToContact = 'ready_to_contact';
    case Contacted = 'contacted';
    case Replied = 'replied';
    case Interested = 'interested';
    case FollowUp = 'follow_up';
    case ProposalRequested = 'proposal_requested';
    case ProposalSent = 'proposal_sent';
    case Negotiating = 'negotiating';
    case Won = 'won';
    case Lost = 'lost';
    case NotInterested = 'not_interested';
    case DoNotContact = 'do_not_contact';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Researching => 'Researching',
            self::ReadyToContact => 'Ready to Contact',
            self::Contacted => 'Contacted',
            self::Replied => 'Replied',
            self::Interested => 'Interested',
            self::FollowUp => 'Follow-up',
            self::ProposalRequested => 'Proposal Requested',
            self::ProposalSent => 'Proposal Sent',
            self::Negotiating => 'Negotiating',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::NotInterested => 'Not Interested',
            self::DoNotContact => 'Do Not Contact',
        };
    }

    /**
     * The badge tone used by the frontend status badge component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::New, self::Researching => 'slate',
            self::ReadyToContact => 'sky',
            self::Contacted, self::FollowUp => 'indigo',
            self::Replied, self::Interested => 'emerald',
            self::ProposalRequested, self::ProposalSent, self::Negotiating => 'amber',
            self::Won => 'green',
            self::Lost, self::NotInterested => 'rose',
            self::DoNotContact => 'red',
        };
    }

    /**
     * Statuses that permanently remove a contact from automatic outreach.
     *
     * @return array<int, self>
     */
    public static function blockedFromOutreach(): array
    {
        return [self::DoNotContact, self::NotInterested, self::Lost, self::Won];
    }

    /**
     * Statuses that mean a live conversation is already underway, so the
     * contact should not be pulled into a fresh cold outreach cycle.
     *
     * @return array<int, self>
     */
    public static function inConversation(): array
    {
        return [self::Replied, self::Interested, self::ProposalRequested, self::ProposalSent, self::Negotiating];
    }

    /**
     * Statuses eligible to be pulled into an outreach cycle automatically.
     *
     * @return array<int, self>
     */
    public static function outreachReady(): array
    {
        return [self::New, self::Researching, self::ReadyToContact, self::Contacted, self::FollowUp];
    }

    public function blocksOutreach(): bool
    {
        return in_array($this, self::blockedFromOutreach(), true);
    }

    public function isInConversation(): bool
    {
        return in_array($this, self::inConversation(), true);
    }

    /**
     * Whether the status represents a contact that has been reached at least once.
     */
    public function hasBeenContacted(): bool
    {
        return ! $this->in(self::New, self::Researching, self::ReadyToContact);
    }

    /**
     * The pipeline stage a contact in this status maps onto.
     */
    public function pipelineStage(): OpportunityStage
    {
        return match ($this) {
            self::New, self::Researching, self::ReadyToContact => OpportunityStage::New,
            self::Contacted, self::FollowUp => OpportunityStage::Contacted,
            self::Replied, self::Interested => OpportunityStage::Interested,
            self::ProposalRequested, self::ProposalSent => OpportunityStage::Proposal,
            self::Negotiating => OpportunityStage::Negotiation,
            self::Won => OpportunityStage::Won,
            self::Lost, self::NotInterested, self::DoNotContact => OpportunityStage::Lost,
        };
    }
}
