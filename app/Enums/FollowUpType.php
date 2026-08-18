<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum FollowUpType: string
{
    use HasEnumHelpers;

    case Email = 'email';
    case Call = 'call';
    case WhatsApp = 'whatsapp';
    case Meeting = 'meeting';
    case LinkedIn = 'linkedin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Call => 'Phone call',
            self::WhatsApp => 'WhatsApp',
            self::Meeting => 'Meeting',
            self::LinkedIn => 'LinkedIn',
            self::Other => 'Other',
        };
    }
}
