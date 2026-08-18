<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\AssignmentStatus;
use App\Enums\OutreachStrategy;
use Database\Factories\OutreachAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One prospect placed inside one outreach cycle: a row of the weekly queue.
 *
 * @property string $id
 * @property int $user_id
 * @property string $outreach_cycle_id
 * @property string $contact_id
 * @property AssignmentStatus $status
 * @property int $rank
 * @property int $score
 * @property array<int, string>|null $reasons
 * @property OutreachStrategy $strategy
 * @property string|null $suggested_service_id
 * @property string|null $suggested_angle
 * @property Carbon $assigned_on
 * @property Carbon|null $contacted_at
 * @property Carbon|null $skipped_at
 * @property Carbon|null $snoozed_until
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact $contact
 * @property-read OutreachCycle $outreachCycle
 * @property-read Service|null $suggestedService
 */
#[Fillable([
    'outreach_cycle_id', 'contact_id', 'status', 'rank', 'score', 'reasons', 'strategy',
    'suggested_service_id', 'suggested_angle', 'assigned_on', 'contacted_at',
    'skipped_at', 'snoozed_until', 'notes',
])]
class OutreachAssignment extends Model
{
    use BelongsToUser;

    /** @use HasFactory<OutreachAssignmentFactory> */
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
            'status' => AssignmentStatus::class,
            'strategy' => OutreachStrategy::class,
            'reasons' => 'array',
            'rank' => 'integer',
            'score' => 'integer',
            'assigned_on' => 'date',
            'contacted_at' => 'datetime',
            'skipped_at' => 'datetime',
            'snoozed_until' => 'date',
        ];
    }

    /**
     * @return BelongsTo<OutreachCycle, $this>
     */
    public function outreachCycle(): BelongsTo
    {
        return $this->belongsTo(OutreachCycle::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function suggestedService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'suggested_service_id');
    }

    /**
     * @return HasMany<EmailMessage, $this>
     */
    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'outreach_assignment_id');
    }

    /**
     * Whether this row still needs the user to do something today.
     */
    public function isDue(): bool
    {
        return $this->status->isOutstanding()
            && ($this->snoozed_until === null || ! $this->snoozed_until->isAfter(Carbon::today()));
    }

    /**
     * Limit the query to rows that have not been actioned yet.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function outstanding(Builder $query): void
    {
        $query->whereIn('status', AssignmentStatus::outstanding());
    }

    /**
     * Limit the query to the work waiting on the user today.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function due(Builder $query): void
    {
        $query->outstanding()
            ->where(function (Builder $query): void {
                $query->whereNull('snoozed_until')
                    ->orWhere('snoozed_until', '<=', Carbon::today());
            });
    }

    /**
     * Order the query by the ranking the queue generator produced.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ranked(Builder $query): void
    {
        $query->orderBy('rank')->orderByDesc('score');
    }
}
