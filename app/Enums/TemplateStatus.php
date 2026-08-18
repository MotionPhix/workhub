<?php

namespace App\Enums;

use App\Concerns\HasEnumHelpers;

enum TemplateStatus: string
{
    use HasEnumHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Active => 'emerald',
            self::Archived => 'amber',
        };
    }
}
