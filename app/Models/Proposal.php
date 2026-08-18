<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ProposalStatus;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A quote sent to a contact. The content itself lives in versions so a revised
 * proposal never overwrites what the client has already seen.
 *
 * @property string $id
 * @property int $user_id
 * @property string $contact_id
 * @property string|null $company_id
 * @property string|null $opportunity_id
 * @property string|null $proposal_template_id
 * @property string $number
 * @property string $title
 * @property string|null $project_name
 * @property ProposalStatus $status
 * @property string $currency
 * @property float $total_amount
 * @property int $current_version
 * @property Carbon|null $valid_until
 * @property Carbon|null $sent_at
 * @property Carbon|null $viewed_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $expired_at
 * @property string $public_token
 * @property int $view_count
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Contact $contact
 * @property-read Collection<int, ProposalVersion> $versions
 */
#[Fillable([
    'contact_id', 'company_id', 'opportunity_id', 'proposal_template_id', 'number',
    'title', 'project_name', 'status', 'currency', 'total_amount', 'current_version',
    'valid_until', 'sent_at', 'viewed_at', 'accepted_at', 'rejected_at', 'expired_at',
    'view_count', 'notes',
])]
#[Hidden(['public_token'])]
class Proposal extends Model
{
    use BelongsToUser;

    /** @use HasFactory<ProposalFactory> */
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
            'status' => ProposalStatus::class,
            'total_amount' => 'decimal:2',
            'current_version' => 'integer',
            'view_count' => 'integer',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $proposal): void {
            $proposal->public_token ??= (string) Str::uuid7();
            $proposal->number ??= static::nextNumber($proposal->user_id);
        });
    }

    /**
     * The next reference number in the user's yearly sequence.
     */
    public static function nextNumber(?int $userId = null, ?Carbon $date = null): string
    {
        $prefix = 'PRO-'.($date ?? Carbon::today())->year.'-';

        $latest = static::withTrashed()
            ->when($userId !== null, fn (Builder $query) => $query->where('user_id', $userId))
            ->where('number', 'like', "{$prefix}%")
            ->orderByDesc('number')
            ->value('number');

        $sequence = $latest === null ? 1 : ((int) Str::afterLast($latest, '-')) + 1;

        return $prefix.Str::padLeft((string) $sequence, 4, '0');
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
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return BelongsTo<ProposalTemplate, $this>
     */
    public function proposalTemplate(): BelongsTo
    {
        return $this->belongsTo(ProposalTemplate::class);
    }

    /**
     * Every revision, newest first.
     *
     * @return HasMany<ProposalVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProposalVersion::class)->orderByDesc('version');
    }

    /**
     * The revision that counts as the proposal right now.
     *
     * @return HasOne<ProposalVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProposalVersion::class)->ofMany('version', 'max');
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isAwaitingDecision(): bool
    {
        return $this->status->isAwaitingDecision();
    }

    /**
     * Whether the proposal has passed its validity date without a decision.
     */
    public function hasLapsed(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->isBefore(Carbon::today())
            && $this->isAwaitingDecision();
    }

    /**
     * Days since the proposal was sent, or null while it is unsent.
     */
    public function daysSinceSent(): ?int
    {
        if ($this->sent_at === null) {
            return null;
        }

        return (int) $this->sent_at->copy()->startOfDay()->diffInDays(Carbon::today());
    }

    /**
     * Limit the query to proposals waiting on a client decision.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function awaitingDecision(Builder $query): void
    {
        $query->whereIn('status', [
            ProposalStatus::Sent,
            ProposalStatus::Viewed,
        ]);
    }

    /**
     * Limit the query to proposals that have gone out to a client.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function sent(Builder $query): void
    {
        $query->whereNotNull('sent_at');
    }

    /**
     * Limit the query to proposals whose validity has expired unanswered.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function lapsed(Builder $query): void
    {
        $query->awaitingDecision()
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', Carbon::today());
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
            foreach (['number', 'title', 'project_name'] as $column) {
                $query->orWhere($column, 'like', "%{$term}%");
            }

            $query->orWhereHas('contact', fn (Builder $contact) => $contact->search($term));
        });
    }
}
