<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum ProposalStatus: string
{
    use HasEnumHelpers;

    case Draft = 'draft';
    case InternalReview = 'internal_review';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InternalReview => 'Internal Review',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::InternalReview => 'sky',
            self::Sent => 'indigo',
            self::Viewed => 'violet',
            self::Accepted => 'emerald',
            self::Rejected => 'rose',
            self::Expired => 'amber',
        };
    }

    /**
     * Whether the proposal is still awaiting a decision from the client.
     */
    public function isAwaitingDecision(): bool
    {
        return $this->in(self::Sent, self::Viewed);
    }

    public function isEditable(): bool
    {
        return $this->in(self::Draft, self::InternalReview);
    }
}
