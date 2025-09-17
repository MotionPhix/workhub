<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SecurityToken extends Model
{
    use BootUuid;

    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function generate(User $user, string $type, int $expirationMinutes = 60)
    {
        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'type' => $type,
            'expires_at' => now()->addMinutes($expirationMinutes),
        ]);
    }

    public function isValid()
    {
        return ! $this->is_used && now()->lessThan($this->expires_at);
    }

    public function markAsUsed()
    {
        $this->update(['is_used' => true]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
