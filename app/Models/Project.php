<?php

namespace App\Models;

use App\Traits\BootUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Casts\CleanHtmlInput;
use Spatie\Tags\HasTags;

class Project extends Model
{
  use BootUuid, HasTags;

  protected $fillable = [
    'name',
    'description',
    'department_uuid',
    'manager_id',
    'start_date',
    'due_date',
    'status',
    'priority',
    'completion_percentage',
    'is_shared',
    'estimated_hours',
    'actual_hours',
  ];

  protected $casts = [
    'start_date' => 'date',
    'due_date' => 'date',
    'is_shared' => 'boolean',
    'estimated_hours' => 'float',
    'actual_hours' => 'float',
    'completion_percentage' => 'integer',
  ];

  // Relationships
  public function workEntries(): HasMany
  {
    return $this->hasMany(WorkEntry::class);
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class, 'department_uuid', 'uuid');
  }

  public function manager(): BelongsTo
  {
    return $this->belongsTo(User::class, 'manager_id');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  public function scopeShared($query)
  {
    return $query->where('is_shared', true);
  }

  public function scopeOverdue($query)
  {
    return $query->where('due_date', '<', now())
      ->where('status', '!=', 'completed');
  }

  public function scopeByDepartment($query, $departmentUuid)
  {
    return $query->where('department_uuid', $departmentUuid);
  }

  // Methods
  public function updateProgress(): void
  {
    $totalEntries = $this->workEntries()->count();
    if ($totalEntries > 0) {
      $completedEntries = $this->workEntries()
        ->where('status', 'completed')
        ->count();

      $this->completion_percentage = ($completedEntries / $totalEntries) * 100;
      $this->actual_hours = $this->workEntries()->sum('hours_worked');

      if ($this->completion_percentage >= 100) {
        $this->status = 'completed';
      }

      $this->save();
    }
  }

  public function isOverdue(): bool
  {
    return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
  }

  public function getEfficiencyRate(): float
  {
    if ($this->estimated_hours <= 0) {
      return 0;
    }
    return min(($this->estimated_hours / max($this->actual_hours, 1)) * 100, 100);
  }
}
