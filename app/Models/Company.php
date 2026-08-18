<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\CompanySize;
use App\Enums\LeadSource;
use App\Enums\OrganizationType;
use App\Enums\QualityRating;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An organisation that may have several contacts working for it.
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $industry
 * @property string|null $location
 * @property string|null $country
 * @property CompanySize|null $size
 * @property OrganizationType|null $organization_type
 * @property LeadSource|null $lead_source
 * @property string|null $linkedin
 * @property string|null $facebook
 * @property string|null $twitter
 * @property string|null $instagram
 * @property float|null $estimated_budget
 * @property string|null $services_offered
 * @property string|null $notes
 * @property string|null $description
 * @property string|null $what_they_do
 * @property QualityRating|null $website_quality
 * @property QualityRating|null $branding_quality
 * @property string|null $social_presence
 * @property string|null $potential_problems
 * @property string|null $opportunities
 * @property string|null $suggested_pitch
 * @property Carbon|null $researched_at
 * @property bool $is_client
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Contact> $contacts
 * @property-read Collection<int, Tag> $tags
 */
#[Fillable([
    'name', 'slug', 'email', 'phone', 'website', 'industry', 'location', 'country', 'size',
    'organization_type', 'lead_source', 'linkedin', 'facebook', 'twitter', 'instagram',
    'estimated_budget', 'services_offered', 'notes', 'description', 'what_they_do',
    'website_quality', 'branding_quality', 'social_presence', 'potential_problems',
    'opportunities', 'suggested_pitch', 'researched_at', 'is_client',
])]
class Company extends Model
{
    use BelongsToUser;

    /** @use HasFactory<CompanyFactory> */
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
            'size' => CompanySize::class,
            'organization_type' => OrganizationType::class,
            'lead_source' => LeadSource::class,
            'website_quality' => QualityRating::class,
            'branding_quality' => QualityRating::class,
            'estimated_budget' => 'decimal:2',
            'researched_at' => 'datetime',
            'is_client' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $company): void {
            if ($company->slug === null || $company->isDirty('name')) {
                $company->slug = static::uniqueSlug($company->name, $company->id);
            }
        });
    }

    /**
     * Build a slug that is unique for the owning user.
     */
    protected static function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasOne<Contact, $this>
     */
    public function primaryContact(): HasOne
    {
        return $this->hasOne(Contact::class)
            ->orderByDesc('is_primary_contact')
            ->orderByDesc('lead_score');
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
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
     * @return HasOne<Client, $this>
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Whether enough research has been captured to pitch with confidence.
     */
    public function isResearched(): bool
    {
        return $this->researched_at !== null;
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
            foreach (['name', 'email', 'industry', 'location', 'website', 'notes'] as $column) {
                $query->orWhere($column, 'like', "%{$term}%");
            }
        });
    }
}
