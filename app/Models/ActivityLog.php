<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;

class ActivityLog extends Model
{
  use BootUuid;

  protected $fillable = [
    'user_id',
    'action',
    'description',
    'ip_address',
    'user_agent',
    'subject_type',
    'subject_id',
    'metadata',
    'severity',
    'country',
    'city'
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'metadata' => 'array'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function subject()
  {
    return $this->morphTo();
  }

  public static function log(
    ?User $user = null,
    string $action,
    string $description,
    array $options = []
  ): self {
    // Determine IP and location
    $ipAddress = Request::ip();
    $location = Location::get($ipAddress);

    // Prepare metadata
    $metadata = $options['metadata'] ?? [];

    // Determine subject
    $subject = $options['subject'] ?? null;

    // Determine severity
    $severity = $options['severity'] ?? 'info';

    // Get caller file and line
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[1] ?? [];
    $file = $caller['file'] ?? 'Unknown file';
    $line = $caller['line'] ?? 'Unknown line';

    // Add caller details to metadata
    $metadata['file'] = $file;
    $metadata['line'] = $line;

    return self::create([
      'user_id' => $user && $user->id ?? null,
      'action' => $action,
      'description' => $description,
      'ip_address' => $ipAddress,
      'user_agent' => Request::userAgent(),
      'subject_type' => $subject && get_class($subject),
      'subject_id' => $subject && $subject->id,
      'metadata' => $metadata,
      'severity' => $severity,
      'country' => $location && $location->countryName,
      'city' => $location && $location->cityName
    ]);
  }

  /**
   * Scope to filter logs by various criteria
   */
  public function scopeFilterBy($query, array $filters)
  {
    if (isset($filters['action'])) {
      $query->where('action', $filters['action']);
    }

    if (isset($filters['severity'])) {
      $query->where('severity', $filters['severity']);
    }

    if (isset($filters['user_id'])) {
      $query->where('user_id', $filters['user_id']);
    }

    if (isset($filters['date_from'])) {
      $query->where('created_at', '>=', $filters['date_from']);
    }

    if (isset($filters['date_to'])) {
      $query->where('created_at', '<=', $filters['date_to']);
    }

    return $query;
  }

  /**
   * Get logs for a specific model
   *
   * @param Model $model
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getLogsForModel(Model $model)
  {
    return self::where('subject_type', get_class($model))
      ->where('subject_id', $model->id)
      ->orderBy('created_at', 'desc')
      ->get();
  }
}
