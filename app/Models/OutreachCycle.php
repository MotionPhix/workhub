<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\CycleStatus;
use Database\Factories\OutreachCycleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One week of planned outreach, holding the prospects chosen for that week.
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property CycleStatus $status
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property int $target_count
 * @property string $month_key
 * @property int $week_of_month
 * @property string|null $notes
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, OutreachAssignment> $assignments
 */
#[Fillable([
    'name', 'status', 'starts_on', 'ends_on', 'target_count',
    'month_key', 'week_of_month', 'notes', 'closed_at',
])]
class OutreachCycle extends Model
{
    use BelongsToUser;

    /** @use HasFactory<OutreachCycleFactory> */
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
            'status' => CycleStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'target_count' => 'integer',
            'week_of_month' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OutreachAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(OutreachAssignment::class);
    }

    /**
     * The prospects picked for this week, ordered by rank.
     *
     * @return BelongsToMany<Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'outreach_assignments')
            ->withPivot(['status', 'rank', 'score', 'strategy', 'contacted_at'])
            ->orderBy('outreach_assignments.rank');
    }

    /**
     * @return HasMany<EmailMessage, $this>
     */
    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    /**
     * Whether today falls inside this cycle.
     */
    public function isCurrent(): bool
    {
        return Carbon::today()->betweenIncluded($this->starts_on, $this->ends_on);
    }

    /**
     * The share of the cycle's assignments that have been actioned.
     */
    public function completionPercentage(): int
    {
        $total = $this->assignments()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->assignments()->whereNotNull('contacted_at')->count();

        return (int) round($done / $total * 100);
    }

    /**
     * Limit the query to the cycle covering today.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('starts_on', '<=', Carbon::today())
            ->where('ends_on', '>=', Carbon::today());
    }

    /**
     * Limit the query to the cycles inside one monthly rotation.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function forMonth(Builder $query, string $monthKey): void
    {
        $query->where('month_key', $monthKey);
    }

    /**
     * Order the query with the most recent cycle first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('starts_on');
    }
}
