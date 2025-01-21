<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordResetToken extends Model
{
  protected $fillable = [
    'user_id',
    'token',
    'created_at',
    'expires_at'
  ];

  protected $dates = [
    'created_at',
    'expires_at'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Check if token is expired
   *
   * @return bool
   */
  public function isExpired(): bool
  {
    return $this->expires_at->isPast();
  }
}
