<?php

namespace App\Models;


use App\Traits\BootableUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable, HasRoles, HasApiTokens, BootableUuid, InteractsWithMedia, SoftDeletes;

  protected $fillable = [
    'name',
    'email',
    'password',
    'department',
    'manager_email',
    'is_active',
    'joined_at',
    'last_login_at',
    'last_login_ip',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
    'settings',
  ];

  protected $hidden = [
    'password',
    'remember_token',
    'last_login_ip',
    'last_login_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'two_factor_confirmed_at' => 'datetime',
      'last_login_at' => 'datetime',
      'password' => 'hashed',
      'is_active' => 'boolean',
      'joined_at' => 'date',
      'settings' => 'array',
    ];
  }

  // Register media conversions for different use cases
  public function registerMediaConversions(Media $media = null): void
  {
    // Avatar conversions
    $this->addMediaConversion('avatar')
      ->width(250)
      ->height(250)
      ->crop('crop-center', 250, 250)
      ->sharpen(10);

    // Thumbnail conversion
    $this->addMediaConversion('thumb')
      ->width(100)
      ->height(100)
      ->crop('crop-center', 100, 100);
  }

  // Custom method to set avatar
  public function setAvatar($file)
  {
    // Clear existing avatar
    $this->clearMediaCollection('avatar');

    // Add new avatar
    return $this->addMedia($file)
      ->toMediaCollection('avatar');
  }

  // Get avatar URL
  public function getAvatarUrl($conversion = 'avatar')
  {
    $media = $this->getFirstMedia('avatar');
    return $media ? $media->getUrl($conversion) : null;
  }

  // Relationships
  public function workEntries()
  {
    return $this->hasMany(WorkEntry::class);
  }

  public function manager()
  {
    return $this->belongsTo(User::class, 'manager_email', 'email');
  }

  public function activityLogs()
  {
    return $this->hasMany(ActivityLog::class);
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  public function scopeVerified($query)
  {
    return $query->whereNotNull('email_verified_at');
  }

  // Attributes
  public function getIsAdminAttribute()
  {
    return $this->hasRole('admin');
  }

  // Methods
  public function recordLogin($request)
  {
    $this->last_login_at = now();
    $this->last_login_ip = $request->ip();
    $this->save();
  }

  public function canAccessWorkEntry(WorkEntry $workEntry)
  {
    // Admin or owner can access
    return $this->is_admin || $this->id === $workEntry->user_id;
  }

  // Security method to check account status
  public function isAccountActive()
  {
    return $this->is_active && !$this->trashed();
  }
}
