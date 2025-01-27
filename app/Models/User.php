<?php

namespace App\Models;


use App\Traits\BootableUuid;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    'gender',
    'department_uuid',
    'manager_email',
    'job_title',
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

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('avatar')
      ->singleFile() // Ensures only one avatar is kept
      ->useFallbackUrl(url($this->defaultAvatar($this->gender)))
      ->registerMediaConversions(function (Media $media) {
        $this->addMediaConversion('thumb')
          ->width(150)
          ->height(150)
          ->sharpen(10);

        $this->addMediaConversion('medium')
          ->width(300)
          ->height(300);
      });
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
  public function avatar($conversion = 'avatar'): Attribute
  {
    return Attribute::get(
      fn() => $this->getFirstMediaUrl('avatar', 'thumb')
        ?: $this->getFirstMediaUrl('avatar')
          ?: url($this->defaultAvatar($this->gendeer))
    );
  }

  private function defaultAvatar(string $gender = null): string
  {
    return $gender
      ? $gender === 'male' ? '/default-m-avatar.png' : '/default-f-avatar.png'
      : '/default-m-avatar.png';
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

  public function department()
  {
    return $this->belongsTo(Department::class, 'department_uuid', 'uuid');
  }

  public function activityLogs()
  {
    return $this->hasMany(ActivityLog::class);
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('is_active', true)
      ->orWhere('last_login_at', '>=', Carbon::now()->subDays(30));
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
