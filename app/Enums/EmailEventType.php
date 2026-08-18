<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum EmailEventType: string
{
    use HasEnumHelpers;

    case Created = 'created';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Replied = 'replied';
    case Bounced = 'bounced';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Draft created',
            self::Queued => 'Queued for sending',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Opened => 'Opened',
            self::Clicked => 'Link clicked',
            self::Replied => 'Replied',
            self::Bounced => 'Bounced',
            self::Failed => 'Failed to send',
        };
    }
}
