<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\TemplateStatus;
use Database\Factories\ProposalTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reusable proposal structure: the ordered sections and the default terms.
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property array<int, array{key: string, title: string, body: string, enabled: bool}> $sections
 * @property string|null $default_terms
 * @property string $currency
 * @property TemplateStatus $status
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'description', 'sections', 'default_terms',
    'currency', 'status', 'is_default',
])]
class ProposalTemplate extends Model
{
    use BelongsToUser;

    /** @use HasFactory<ProposalTemplateFactory> */
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
            'sections' => 'array',
            'status' => TemplateStatus::class,
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $template): void {
            if ($template->is_default) {
                static::query()
                    ->where('user_id', $template->user_id)
                    ->whereKeyNot($template->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * Only the sections that should appear in a generated proposal.
     *
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    public function enabledSections(): array
    {
        return array_values(array_filter(
            $this->sections,
            fn (array $section): bool => $section['enabled'] ?? true,
        ));
    }

    /**
     * Limit the query to templates available for new proposals.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', TemplateStatus::Active);
    }
}
