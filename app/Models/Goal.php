<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The targets for one month: how much outreach and how many wins are expected.
 *
 * @property string $id
 * @property int $user_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property int $prospect_target
 * @property int $follow_up_target
 * @property int $proposal_target
 * @property int $client_target
 * @property float|null $revenue_target
 * @property string $currency
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'period_start', 'period_end', 'prospect_target', 'follow_up_target',
    'proposal_target', 'client_target', 'revenue_target', 'currency', 'notes',
])]
class Goal extends Model
{
    use BelongsToUser;

    /** @use HasFactory<GoalFactory> */
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
            'period_start' => 'date',
            'period_end' => 'date',
            'prospect_target' => 'integer',
            'follow_up_target' => 'integer',
            'proposal_target' => 'integer',
            'client_target' => 'integer',
            'revenue_target' => 'decimal:2',
        ];
    }

    /**
     * Whether the given date falls inside the goal's period.
     */
    public function covers(Carbon $date): bool
    {
        return $date->betweenIncluded($this->period_start, $this->period_end);
    }

    /**
     * The month this goal belongs to, in the format the cycles use.
     */
    public function monthKey(): string
    {
        return $this->period_start->format('Y-m');
    }

    /**
     * Limit the query to the goal covering today.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('period_start', '<=', Carbon::today())
            ->where('period_end', '>=', Carbon::today());
    }

    /**
     * Limit the query to the goal for the month containing the given date.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function forMonth(Builder $query, Carbon $date): void
    {
        $query->whereDate('period_start', $date->copy()->startOfMonth());
    }
}
