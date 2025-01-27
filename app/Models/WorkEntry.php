<?php

namespace App\Models;

use App\Traits\BootUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Casts\CleanHtmlInput;
use Spatie\Tags\HasTags;

class WorkEntry extends Model
{
  use HasFactory, BootUuid, HasTags;

  protected $fillable = [
    'user_id',
    'work_date',
    'work_title',
    'description',
    'hours_worked',
    'status'
  ];

  protected $casts = [
    'work_date' => 'date:Y-m-d',
    'description'    => CleanHtmlInput::class,
    'tags' => 'array'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Update the work entry with the given attributes
   *
   * @param array $attributes
   * @return bool
   */
  public function updateEntry(array $attributes): bool
  {
    return DB::transaction(function () use ($attributes) {
      // Validate work date
      if (isset($attributes['work_date'])) {
        $attributes['work_date'] = Carbon::parse($attributes['work_date'])->format('Y-m-d');
      }

      // Validate hours worked
      if (isset($attributes['hours_worked'])) {
        $attributes['hours_worked'] = (float) $attributes['hours_worked'];
      }

      // Handle tags if present
      if (isset($attributes['tags'])) {
        $tags = $attributes['tags'];
        unset($attributes['tags']);
        $this->syncTags($tags);
      }

      // Update the model attributes
      $updated = $this->update(array_filter($attributes));

      // Fire model events if needed
      if ($updated) {
        $this->fireModelEvent('updated', false);
      }

      return $updated;
    });
  }

  /**
   * Scope a query to only include entries for a specific user
   *
   * @param Builder $query
   * @param int $userId
   * @return Builder
   */
  public function scopeForUser(Builder $query, int $userId): Builder
  {
    return $query->where('user_id', $userId);
  }
}
