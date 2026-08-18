<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A free form label that can be attached to contacts and companies.
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'color'])]
class Tag extends Model
{
    use BelongsToUser;

    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasUuids;

    protected static function booted(): void
    {
        static::saving(function (self $tag): void {
            if ($tag->slug === null || $tag->isDirty('name')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * @return MorphToMany<Contact, $this>
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, 'taggable');
    }

    /**
     * @return MorphToMany<Company, $this>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taggable');
    }
}
