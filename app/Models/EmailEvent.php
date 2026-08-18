<?php

namespace App\Models;

use App\Enums\EmailEventType;
use Database\Factories\EmailEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An immutable transport event belonging to one email, such as a delivery or open.
 *
 * @property string $id
 * @property string $email_message_id
 * @property EmailEventType $type
 * @property Carbon $occurred_at
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EmailMessage $emailMessage
 */
#[Fillable(['email_message_id', 'type', 'occurred_at', 'meta'])]
class EmailEvent extends Model
{
    /** @use HasFactory<EmailEventFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EmailEventType::class,
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<EmailMessage, $this>
     */
    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }
}
