<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\OpportunityStage;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A potential piece of work being tracked through the pipeline.
 *
 * @property string $id
 * @property int $user_id
 * @property string $contact_id
 * @property string|null $company_id
 * @property string|null $service_id
 * @property string $title
 * @property OpportunityStage $stage
 * @property float $value
 * @property string $currency
 * @property int $probability
 * @property Carbon|null $expected_close_on
 * @property string|null $source
 * @property string|null $notes
 * @property Carbon|null $won_at
 * @property Carbon|null $lost_at
 * @property string|null $lost_reason
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Contact $contact
 * @property-read Company|null $company
 * @property-read Service|null $service
 */
#[Fillable([
    'contact_id', 'company_id', 'service_id', 'title', 'stage', 'value', 'currency',
    'probability', 'expected_close_on', 'source', 'notes', 'won_at', 'lost_at',
    'lost_reason', 'last_activity_at',
])]
class Opportunity extends Model
{
    use BelongsToUser;

    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    use HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_on' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'last_activity_at' => 'datetime',
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
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * @return HasOne<Client, $this>
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /**
     * The value discounted by the chance of winning, used for forecasting.
     */
    public function weightedValue(): float
    {
        return round((float) $this->value * $this->probability / 100, 2);
    }

    public function isOpen(): bool
    {
        return $this->stage->isOpen();
    }

    /**
     * Limit the query to opportunities still in play.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNotIn('stage', [OpportunityStage::Won, OpportunityStage::Lost]);
    }

    /**
     * Limit the query to one pipeline column.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inStage(Builder $query, OpportunityStage $stage): void
    {
        $query->where('stage', $stage);
    }

    /**
     * Limit the query to opportunities won within the given dates.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function wonBetween(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->where('stage', OpportunityStage::Won)
            ->whereBetween('won_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    /**
     * Order the query so the biggest, soonest opportunities come first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function mostValuableFirst(Builder $query): void
    {
        $query->orderByDesc('value')->orderBy('expected_close_on');
    }
}
