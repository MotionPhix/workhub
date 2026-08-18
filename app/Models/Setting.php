<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * A single configurable value, such as an outreach rule or an SMTP credential.
 *
 * Secrets are stored encrypted. Encryption is applied by the settings
 * repository rather than by a cast, so the flag is always written before the
 * value and a mis-ordered mass assignment can never store a secret in plain
 * text. The raw column is hidden from serialisation so a secret cannot reach
 * the frontend by accident.
 *
 * @property string $id
 * @property int $user_id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property bool $is_encrypted
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['group', 'key', 'value', 'is_encrypted'])]
#[Hidden(['value'])]
class Setting extends Model
{
    use BelongsToUser, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * The value in plain text, decrypting it first when it is a secret.
     */
    public function plainValue(): ?string
    {
        if ($this->value === null || ! $this->is_encrypted) {
            return $this->value;
        }

        return Crypt::decryptString($this->value);
    }

    /**
     * Whether a secret has been supplied, without revealing it.
     */
    public function isFilled(): bool
    {
        return $this->value !== null && $this->value !== '';
    }

    /**
     * Limit the query to one group of settings.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function inGroup(Builder $query, string $group): void
    {
        $query->where('group', $group);
    }
}
