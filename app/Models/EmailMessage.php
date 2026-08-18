<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\EmailDirection;
use App\Enums\EmailStatus;
use App\Enums\OutreachStrategy;
use Database\Factories\EmailMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A single email, either drafted and sent by the user or logged as a reply.
 *
 * Rendered copy is stored on the row rather than re-rendered from the template,
 * so what was sent can always be shown exactly as the recipient received it.
 *
 * @property string $id
 * @property int $user_id
 * @property string $contact_id
 * @property string|null $company_id
 * @property string|null $email_template_id
 * @property string|null $outreach_cycle_id
 * @property string|null $outreach_assignment_id
 * @property string|null $parent_id
 * @property EmailDirection $direction
 * @property OutreachStrategy $strategy
 * @property EmailStatus $status
 * @property string $subject
 * @property string $body
 * @property array<string, string>|null $variables
 * @property string $to_email
 * @property string|null $to_name
 * @property string $from_email
 * @property string|null $from_name
 * @property string|null $reply_to
 * @property Carbon|null $queued_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $opened_at
 * @property Carbon|null $replied_at
 * @property Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property string|null $message_id
 * @property string|null $tracking_token
 * @property int $opens
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact $contact
 * @property-read Collection<int, EmailEvent> $events
 */
#[Fillable([
    'contact_id', 'company_id', 'email_template_id', 'outreach_cycle_id',
    'outreach_assignment_id', 'parent_id', 'direction', 'strategy', 'status',
    'subject', 'body', 'variables', 'to_email', 'to_name', 'from_email', 'from_name',
    'reply_to', 'queued_at', 'sent_at', 'delivered_at', 'opened_at', 'replied_at',
    'failed_at', 'failure_reason', 'message_id', 'opens',
])]
class EmailMessage extends Model
{
    use BelongsToUser;

    /** @use HasFactory<EmailMessageFactory> */
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
            'direction' => EmailDirection::class,
            'strategy' => OutreachStrategy::class,
            'status' => EmailStatus::class,
            'variables' => 'array',
            'opens' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'replied_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            $message->tracking_token ??= (string) Str::uuid7();
        });
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<EmailTemplate, $this>
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * @return BelongsTo<OutreachCycle, $this>
     */
    public function outreachCycle(): BelongsTo
    {
        return $this->belongsTo(OutreachCycle::class);
    }

    /**
     * @return BelongsTo<OutreachAssignment, $this>
     */
    public function outreachAssignment(): BelongsTo
    {
        return $this->belongsTo(OutreachAssignment::class);
    }

    /**
     * The message this one is a reply to.
     *
     * @return BelongsTo<EmailMessage, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<EmailMessage, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<EmailEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    /**
     * @return HasMany<FollowUp, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function isDraft(): bool
    {
        return $this->status === EmailStatus::Draft;
    }

    public function wasSent(): bool
    {
        return $this->status->wasSent();
    }

    /**
     * Days since the message went out, or null while it is still a draft.
     */
    public function daysSinceSent(): ?int
    {
        if ($this->sent_at === null) {
            return null;
        }

        return (int) $this->sent_at->copy()->startOfDay()->diffInDays(Carbon::today());
    }

    /**
     * Limit the query to messages the user sent.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function outbound(Builder $query): void
    {
        $query->where('direction', EmailDirection::Outbound);
    }

    /**
     * Limit the query to logged replies.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inbound(Builder $query): void
    {
        $query->where('direction', EmailDirection::Inbound);
    }

    /**
     * Limit the query to messages that actually left the application.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function sent(Builder $query): void
    {
        $query->whereNotNull('sent_at');
    }

    /**
     * Limit the query to sent messages that have had no reply.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function awaitingReply(Builder $query): void
    {
        $query->outbound()->sent()->whereNull('replied_at');
    }

    /**
     * Limit the query to messages sent within the given dates.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function sentBetween(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('sent_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }
}
