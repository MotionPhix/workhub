<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Tags\HasTags;

class Project extends Model
{
    use BootUuid, HasFactory, HasTags;

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
        'project_type', // client, internal
        'client_name',
        'client_contact',
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
        return $this->hasMany(WorkEntry::class, 'project_uuid', 'uuid');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_uuid', 'uuid');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function teamMembers()
    {
        return $this->belongsToMany(User::class, 'work_entries', 'project_uuid', 'user_id', 'uuid', 'id')
            ->distinct();
    }

    public function activeTeamMembers()
    {
        return $this->belongsToMany(User::class, 'work_entries', 'project_uuid', 'user_id', 'uuid', 'id')
            ->where('work_entries.created_at', '>=', now()->subDays(30))
            ->distinct();
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

    public function scopeClientProjects($query)
    {
        return $query->where('project_type', 'client');
    }

    public function scopeInternalProjects($query)
    {
        return $query->where('project_type', 'internal');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
            $this->actual_hours = $this->workEntries()
                ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
                ->value('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0;

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

    public function isClientProject(): bool
    {
        return $this->project_type === 'client';
    }

    public function isInternalProject(): bool
    {
        return $this->project_type === 'internal';
    }

    public function getTeamMembersCount(): int
    {
        return $this->workEntries()->distinct('user_id')->count('user_id');
    }

    public function hasActiveWork(): bool
    {
        return $this->workEntries()->where('created_at', '>=', now()->subDays(7))->exists();
    }
}
