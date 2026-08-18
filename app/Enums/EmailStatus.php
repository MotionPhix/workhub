<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum EmailStatus: string
{
    use HasEnumHelpers;

    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Replied = 'replied';
    case Failed = 'failed';
    case Bounced = 'bounced';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Opened => 'Opened',
            self::Replied => 'Replied',
            self::Failed => 'Failed',
            self::Bounced => 'Bounced',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Queued => 'sky',
            self::Sent, self::Delivered => 'indigo',
            self::Opened => 'violet',
            self::Replied => 'emerald',
            self::Failed, self::Bounced => 'rose',
        };
    }

    /**
     * Statuses that count as outreach actually leaving the building.
     *
     * @return array<int, self>
     */
    public static function countsAsSent(): array
    {
        return [self::Sent, self::Delivered, self::Opened, self::Replied];
    }

    public function wasSent(): bool
    {
        return in_array($this, self::countsAsSent(), true);
    }
}
