<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\ContactStatus;
use App\Enums\LeadSource;
use App\Enums\LeadTemperature;
use App\Enums\Priority;
use App\Enums\RelationshipStage;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A person who is either a prospect, an active conversation, or a client contact.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $company_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $job_title
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $linkedin
 * @property string|null $facebook
 * @property string|null $twitter
 * @property string|null $instagram
 * @property string|null $location
 * @property string|null $industry
 * @property ContactStatus $status
 * @property Priority $priority
 * @property int $lead_score
 * @property RelationshipStage $relationship_stage
 * @property LeadSource|null $lead_source
 * @property string|null $primary_service_id
 * @property string|null $potential_needs
 * @property float|null $estimated_budget
 * @property string|null $notes
 * @property bool $is_primary_contact
 * @property Carbon|null $first_contacted_at
 * @property Carbon|null $last_contacted_at
 * @property Carbon|null $responded_at
 * @property int $outreach_count
 * @property string|null $last_outcome
 * @property Carbon|null $cooldown_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $full_name
 * @property-read LeadTemperature $temperature
 * @property-read Company|null $company
 * @property-read Service|null $primaryService
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, EmailMessage> $emailMessages
 * @property-read Collection<int, FollowUp> $followUps
 * @property-read Collection<int, Activity> $activities
 */
#[Fillable([
    'company_id', 'first_name', 'last_name', 'job_title', 'email', 'phone', 'website',
    'linkedin', 'facebook', 'twitter', 'instagram', 'location', 'industry', 'status',
    'priority', 'lead_score', 'relationship_stage', 'lead_source', 'primary_service_id',
    'potential_needs', 'estimated_budget', 'notes', 'is_primary_contact',
    'first_contacted_at', 'last_contacted_at', 'responded_at', 'outreach_count',
    'last_outcome', 'cooldown_until',
])]
#[Appends(['full_name', 'temperature'])]
class Contact extends Model
{
    use BelongsToUser;

    /** @use HasFactory<ContactFactory> */
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
            'status' => ContactStatus::class,
            'priority' => Priority::class,
            'relationship_stage' => RelationshipStage::class,
            'lead_source' => LeadSource::class,
            'estimated_budget' => 'decimal:2',
            'lead_score' => 'integer',
            'outreach_count' => 'integer',
            'is_primary_contact' => 'boolean',
            'first_contacted_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'responded_at' => 'datetime',
            'cooldown_until' => 'date',
        ];
    }

    /**
     * The contact's name as it should be shown in listings and emails.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim("{$this->first_name} ".($this->last_name ?? '')),
        );
    }

    /**
     * How warm the lead is, derived from the lead score.
     */
    protected function temperature(): Attribute
    {
        return Attribute::make(
            get: fn (): LeadTemperature => LeadTemperature::fromScore($this->lead_score),
        );
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The service most likely to be a fit for this contact.
     *
     * @return BelongsTo<Service, $this>
     */
    public function primaryService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'primary_service_id');
    }

    /**
     * Every service this contact could plausibly need.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * @return HasMany<OutreachAssignment, $this>
     */
    public function outreachAssignments(): HasMany
    {
        return $this->hasMany(OutreachAssignment::class);
    }

    /**
     * @return HasMany<EmailMessage, $this>
     */
    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    /**
     * The most recent message either way, used by the queue and timeline headers.
     *
     * @return HasOne<EmailMessage, $this>
     */
    public function latestEmailMessage(): HasOne
    {
        return $this->hasOne(EmailMessage::class)->latestOfMany();
    }

    /**
     * @return HasMany<FollowUp, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /**
     * The next thing owed to this contact.
     *
     * @return HasOne<FollowUp, $this>
     */
    public function nextFollowUp(): HasOne
    {
        return $this->hasOne(FollowUp::class)
            ->pending()
            ->orderBy('due_on');
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Days since the last outbound contact, or null if never contacted.
     */
    public function daysSinceLastContact(): ?int
    {
        if ($this->last_contacted_at === null) {
            return null;
        }

        return (int) $this->last_contacted_at->copy()->startOfDay()->diffInDays(Carbon::today());
    }

    /**
     * Whether the contact is resting inside the configured cooldown window.
     */
    public function isInCooldown(): bool
    {
        return $this->cooldown_until !== null
            && $this->cooldown_until->isAfter(Carbon::today());
    }

    /**
     * Whether this contact may be added to an outreach queue right now.
     */
    public function isContactable(): bool
    {
        return $this->email !== null
            && ! $this->status->blocksOutreach()
            && ! $this->isInCooldown();
    }

    public function hasResponded(): bool
    {
        return $this->responded_at !== null;
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
            foreach (['first_name', 'last_name', 'email', 'job_title', 'industry', 'location', 'notes'] as $column) {
                $query->orWhere($column, 'like', "%{$term}%");
            }

            $query->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$term}%"));
        });
    }

    /**
     * Limit the query to contacts that are eligible for outreach.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function contactable(Builder $query): void
    {
        $query->whereNotNull('email')
            ->whereNotIn('status', ContactStatus::blockedFromOutreach())
            ->where(function (Builder $query): void {
                $query->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<=', Carbon::today());
            });
    }

    /**
     * Limit the query to contacts in an active conversation.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inConversation(Builder $query): void
    {
        $query->whereIn('status', ContactStatus::inConversation());
    }

    /**
     * Limit the query to contacts not contacted since the given date.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function notContactedSince(Builder $query, Carbon $date): void
    {
        $query->where(function (Builder $query) use ($date): void {
            $query->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', $date);
        });
    }

    /**
     * Order the query so the most promising prospects come first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function bestFirst(Builder $query): void
    {
        $query->orderByDesc('lead_score')
            ->orderByRaw('last_contacted_at is null desc')
            ->orderBy('last_contacted_at');
    }
}
