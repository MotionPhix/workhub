<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum EmailDirection: string
{
    use HasEnumHelpers;

    case Outbound = 'outbound';
    case Inbound = 'inbound';

    public function label(): string
    {
        return match ($this) {
            self::Outbound => 'Sent',
            self::Inbound => 'Received',
        };
    }
}
