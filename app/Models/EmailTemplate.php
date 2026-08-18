<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reusable email skeleton containing merge variables.
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property TemplateCategory $category
 * @property string $subject
 * @property string $body
 * @property array<int, string>|null $variables
 * @property TemplateStatus $status
 * @property string|null $notes
 * @property int $version
 * @property string|null $parent_id
 * @property int $times_used
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'category', 'subject', 'body', 'variables', 'status',
    'notes', 'version', 'parent_id', 'times_used', 'last_used_at',
])]
class EmailTemplate extends Model
{
    use BelongsToUser;

    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The pattern every merge variable in a subject or body must follow.
     */
    public const VARIABLE_PATTERN = '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => TemplateCategory::class,
            'status' => TemplateStatus::class,
            'variables' => 'array',
            'version' => 'integer',
            'times_used' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            $template->variables = static::detectVariables($template->subject, $template->body);
        });
    }

    /**
     * Pull the distinct merge variable names out of the given strings.
     *
     * @return array<int, string>
     */
    public static function detectVariables(?string ...$sources): array
    {
        $found = [];

        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            preg_match_all(self::VARIABLE_PATTERN, $source, $matches);

            $found = [...$found, ...$matches[1]];
        }

        return array_values(array_unique(array_map('strtolower', $found)));
    }

    /**
     * The template this one was duplicated from.
     *
     * @return BelongsTo<EmailTemplate, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Later versions duplicated from this template.
     *
     * @return HasMany<EmailTemplate, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<EmailMessage, $this>
     */
    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    /**
     * Limit the query to templates that may be used to compose email.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', TemplateStatus::Active);
    }

    /**
     * Limit the query to one category of template.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, TemplateCategory $category): void
    {
        $query->where('category', $category);
    }

    /**
     * Order the query so the templates that get used most come first.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function mostUsed(Builder $query): void
    {
        $query->orderByDesc('times_used')->orderBy('name');
    }
}
