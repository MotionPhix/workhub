<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum OpportunityStage: string
{
    use HasEnumHelpers;

    case New = 'new';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Interested => 'Interested',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'slate',
            self::Contacted => 'indigo',
            self::Interested => 'sky',
            self::Proposal => 'amber',
            self::Negotiation => 'violet',
            self::Won => 'emerald',
            self::Lost => 'rose',
        };
    }

    /**
     * The default win probability used when a deal moves into this stage.
     */
    public function defaultProbability(): int
    {
        return match ($this) {
            self::New => 10,
            self::Contacted => 20,
            self::Interested => 40,
            self::Proposal => 60,
            self::Negotiation => 80,
            self::Won => 100,
            self::Lost => 0,
        };
    }

    public function isOpen(): bool
    {
        return ! $this->in(self::Won, self::Lost);
    }

    /**
     * The stages shown as columns on the pipeline board, in order.
     *
     * @return array<int, self>
     */
    public static function boardColumns(): array
    {
        return [self::New, self::Contacted, self::Interested, self::Proposal, self::Negotiation, self::Won, self::Lost];
    }

    /**
     * The stages counted in the conversion funnel, in order.
     *
     * @return array<int, self>
     */
    public static function funnelStages(): array
    {
        return [self::New, self::Contacted, self::Interested, self::Proposal, self::Negotiation, self::Won];
    }
}
