<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One entry in the history: what happened, to whom, and when.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $contact_id
 * @property string|null $company_id
 * @property ActivityType $type
 * @property string|null $description
 * @property array<string, mixed>|null $meta
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Company|null $company
 * @property-read Model|null $subject
 */
#[Fillable([
    'subject_type', 'subject_id', 'contact_id', 'company_id',
    'type', 'description', 'meta', 'occurred_at',
])]
class Activity extends Model
{
    use BelongsToUser;

    /** @use HasFactory<ActivityFactory> */
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
            'type' => ActivityType::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * The record the activity happened to.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
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
     * Limit the query to one kind of activity.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, ActivityType ...$types): void
    {
        $query->whereIn('type', $types);
    }

    /**
     * Limit the query to activity inside the given dates.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function occurredBetween(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('occurred_at', [
            $from->copy()->startOfDay(),
            $to->copy()->endOfDay(),
        ]);
    }

    /**
     * Order the query with the most recent activity first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc('occurred_at')->orderByDesc('id');
    }
}
