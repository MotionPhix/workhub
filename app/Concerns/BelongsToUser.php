<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Marks a model as owned by a user.
 *
 * Version 1 of the application is single user, but every record carries an
 * owner so the schema can become multi-tenant without a migration. While a user
 * is authenticated, queries are transparently filtered to that user, which also
 * stops route model binding from ever resolving somebody else's record.
 *
 * Console commands, queued jobs and seeders run without an authenticated user
 * and therefore see every record, which is what background processing needs.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('ownedByCurrentUser', function (Builder $query): void {
            if (Auth::check()) {
                $query->where($query->getModel()->qualifyColumn('user_id'), Auth::id());
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('user_id') === null && Auth::check()) {
                $model->setAttribute('user_id', Auth::id());
            }
        });
    }

    /**
     * The user that owns the record.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limit the query to records owned by the given user.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function forUser(Builder $query, User|int $user): void
    {
        $query->where(
            $this->qualifyColumn('user_id'),
            $user instanceof User ? $user->id : $user,
        );
    }
}
