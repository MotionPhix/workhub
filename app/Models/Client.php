<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ClientStatus;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A prospect who became paying work.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $company_id
 * @property string|null $primary_contact_id
 * @property string|null $opportunity_id
 * @property string $name
 * @property ClientStatus $status
 * @property Carbon $since
 * @property float $total_revenue
 * @property float|null $retainer_amount
 * @property string $currency
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company|null $company
 * @property-read Contact|null $primaryContact
 * @property-read Collection<int, Project> $projects
 */
#[Fillable([
    'company_id', 'primary_contact_id', 'opportunity_id', 'name', 'status', 'since',
    'total_revenue', 'retainer_amount', 'currency', 'notes',
])]
class Client extends Model
{
    use BelongsToUser;

    /** @use HasFactory<ClientFactory> */
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
            'status' => ClientStatus::class,
            'since' => 'date',
            'total_revenue' => 'decimal:2',
            'retainer_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    /**
     * The deal that turned this prospect into a client.
     *
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Whether the relationship is still generating work.
     */
    public function isActive(): bool
    {
        return $this->status === ClientStatus::Active;
    }

    /**
     * Whether the client pays a recurring fee.
     */
    public function isOnRetainer(): bool
    {
        return $this->retainer_amount !== null && (float) $this->retainer_amount > 0;
    }

    /**
     * Limit the query to clients currently being worked with.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ClientStatus::Active);
    }

    /**
     * Limit the query to a free text search across the searchable columns.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%");
        });
    }
}
