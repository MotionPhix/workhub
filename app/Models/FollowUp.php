<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Enums\Priority;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A dated reminder to chase a contact, usually created after sending an email.
 *
 * @property string $id
 * @property int $user_id
 * @property string $contact_id
 * @property string|null $email_message_id
 * @property FollowUpType $type
 * @property FollowUpStatus $status
 * @property Priority $priority
 * @property Carbon $due_on
 * @property string|null $notes
 * @property string|null $outcome
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property int $snooze_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact $contact
 * @property-read EmailMessage|null $emailMessage
 */
#[Fillable([
    'contact_id', 'email_message_id', 'type', 'status', 'priority', 'due_on',
    'notes', 'outcome', 'completed_at', 'cancelled_at', 'snooze_count',
])]
class FollowUp extends Model
{
    use BelongsToUser;

    /** @use HasFactory<FollowUpFactory> */
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
            'type' => FollowUpType::class,
            'status' => FollowUpStatus::class,
            'priority' => Priority::class,
            'due_on' => 'date',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'snooze_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The message that prompted this follow up.
     *
     * @return BelongsTo<EmailMessage, $this>
     */
    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === FollowUpStatus::Pending
            && $this->due_on->isBefore(Carbon::today());
    }

    public function isDueToday(): bool
    {
        return $this->status === FollowUpStatus::Pending
            && $this->due_on->isSameDay(Carbon::today());
    }

    /**
     * How many days late the follow up is, or null when it is not late.
     */
    public function daysOverdue(): ?int
    {
        if (! $this->isOverdue()) {
            return null;
        }

        return (int) $this->due_on->copy()->diffInDays(Carbon::today());
    }

    /**
     * Limit the query to follow ups still owed.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', FollowUpStatus::Pending);
    }

    /**
     * Limit the query to follow ups due today or earlier.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function due(Builder $query): void
    {
        $query->pending()->where('due_on', '<=', Carbon::today());
    }

    /**
     * Limit the query to follow ups due today.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function dueToday(Builder $query): void
    {
        $query->pending()->whereDate('due_on', Carbon::today());
    }

    /**
     * Limit the query to follow ups whose date has passed.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->pending()->where('due_on', '<', Carbon::today());
    }

    /**
     * Limit the query to follow ups due in the next given number of days.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function dueWithin(Builder $query, int $days): void
    {
        $query->pending()->whereBetween('due_on', [
            Carbon::today(),
            Carbon::today()->addDays($days),
        ]);
    }

    /**
     * Order the query so the most urgent work is first.
     *
     * The priority ordering is expressed as a portable case expression because
     * the column stores enum values rather than a sortable weight.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function mostUrgentFirst(Builder $query): void
    {
        $query->orderBy('due_on')
            ->orderByRaw('case priority when ? then 1 when ? then 2 when ? then 3 else 4 end', [
                Priority::Urgent->value,
                Priority::High->value,
                Priority::Medium->value,
            ]);
    }
}
