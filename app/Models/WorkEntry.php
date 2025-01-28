<?php

namespace App\Models;

use App\Traits\BootUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Casts\CleanHtmlInput;
use Spatie\Tags\HasTags;

class WorkEntry extends Model
{
  use HasFactory, BootUuid, HasTags;

  protected $fillable = [
    'user_id',
    'project_id',
    'work_date',
    'work_title',
    'description',
    'hours_worked',
    'status'
  ];

  protected $casts = [
    'work_date' => 'date:Y-m-d',
    'description' => CleanHtmlInput::class,
    'hours_worked' => 'float',
  ];

  // Relationships
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function project(): BelongsTo
  {
    return $this->belongsTo(Project::class);
  }

  // Scopes
  public function scopeForUser(Builder $query, int $userId): Builder
  {
    return $query->where('user_id', $userId);
  }

  public function scopeForProject(Builder $query, int $projectId): Builder
  {
    return $query->where('project_id', $projectId);
  }

  public function scopeCompleted(Builder $query): Builder
  {
    return $query->where('status', 'completed');
  }

  public function scopeInProgress(Builder $query): Builder
  {
    return $query->where('status', '!=', 'completed');
  }

  public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
  {
    return $query->whereBetween('work_date', [$startDate, $endDate]);
  }

  public function scopeThisMonth(Builder $query): Builder
  {
    return $query->whereMonth('work_date', Carbon::now()->month);
  }

  // Methods
  public function updateEntry(array $attributes): bool
  {
    return DB::transaction(function () use ($attributes) {
      if (isset($attributes['work_date'])) {
        $attributes['work_date'] = Carbon::parse($attributes['work_date'])->format('Y-m-d');
      }

      if (isset($attributes['hours_worked'])) {
        $attributes['hours_worked'] = (float) $attributes['hours_worked'];
      }

      if (isset($attributes['tags'])) {
        $tags = $attributes['tags'];
        unset($attributes['tags']);
        $this->syncTags($tags);
      }

      $updated = $this->update(array_filter($attributes));

      if ($updated && $this->project_id) {
        $this->project->updateProgress();
      }

      return $updated;
    });
  }

  protected static function booted()
  {
    static::created(function ($workEntry) {
      if ($workEntry->project_id) {
        $workEntry->project->updateProgress();
      }
    });

    static::updated(function ($workEntry) {
      if ($workEntry->project_id) {
        $workEntry->project->updateProgress();
      }
    });
  }
}
