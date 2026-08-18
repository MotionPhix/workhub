<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A piece of delivered or in progress work for a client.
 *
 * @property string $id
 * @property int $user_id
 * @property string $client_id
 * @property string|null $service_id
 * @property string|null $proposal_id
 * @property string $name
 * @property string|null $description
 * @property ProjectStatus $status
 * @property float $value
 * @property string $currency
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Client $client
 * @property-read Service|null $service
 */
#[Fillable([
    'client_id', 'service_id', 'proposal_id', 'name', 'description',
    'status', 'value', 'currency', 'starts_on', 'ends_on',
])]
class Project extends Model
{
    use BelongsToUser;

    /** @use HasFactory<ProjectFactory> */
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
            'status' => ProjectStatus::class,
            'value' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The proposal this work was sold with.
     *
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Limit the query to work that is not finished.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inFlight(Builder $query): void
    {
        $query->whereIn('status', [
            ProjectStatus::Planned,
            ProjectStatus::InProgress,
            ProjectStatus::Review,
        ]);
    }
}
