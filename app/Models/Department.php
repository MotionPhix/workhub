<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class Department extends Model
{
    use BootableUuid, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'settings',
        'allocated_hours',
        'actual_hours',
        'working_hours_per_day',
        'daily_tasks_target',
        'quality_target_percentage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'allocated_hours' => 'float',
        'actual_hours' => 'float',
        'working_hours_per_day' => 'float',
        'daily_tasks_target' => 'integer',
        'quality_target_percentage' => 'float',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'department_uuid', 'uuid');
    }

    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_email');
    }

    // Management Methods
    public function getManagers(): Collection
    {
        return $this->users()
            ->whereIn('id', function ($query) {
                $query->select('manager_email')
                    ->from('users')
                    ->whereNotNull('manager_email')
                    ->where('department_uuid', $this->uuid);
            })
            ->get();
    }

    public function getDepartmentHierarchy(): array
    {
        $hierarchy = [];
        $departmentHead = $this->departmentHead;

        if ($departmentHead) {
            $hierarchy['department_head'] = [
                'user' => $departmentHead,
                'managers' => [],
            ];

            $managers = $this->getManagers();
            foreach ($managers as $manager) {
                $hierarchy['department_head']['managers'][] = [
                    'user' => $manager,
                    'direct_reports' => $this->getDirectReports($manager->email),
                ];
            }
        }

        return $hierarchy;
    }

    public function getDirectReports(string $managerEmail): Collection
    {
        return $this->users()
            ->where('manager_email', $managerEmail)
            ->get();
    }

    // Scopes
    public function scopeWithManagers(Builder $query): Builder
    {
        return $query->whereHas('users', function ($query) {
            $query->whereIn('email', function ($subquery) {
                $subquery->select('manager_email')
                    ->from('users')
                    ->whereNotNull('manager_email');
            });
        });
    }

    // Helper Methods
    public function isUserManager(User $user): bool
    {
        return $this->users()
            ->where('manager_email', $user->email)
            ->exists();
    }

    public function getUsersUnderManager(string $managerEmail): Collection
    {
        return $this->users()
            ->where('manager_email', $managerEmail)
            ->get();
    }

    public function getAllManagerialStaff(): Collection
    {
        return $this->users()
            ->where(function ($query) {
                $query->whereIn('email', function ($subquery) {
                    $subquery->select('manager_email')
                        ->from('users')
                        ->whereNotNull('manager_email')
                        ->where('department_uuid', $this->uuid);
                })
                    ->orWhere('id', $this->department_head_id);
            })
            ->get();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'department_uuid', 'uuid');
    }

    public function workEntries(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkEntry::class,
            User::class,
            'department_uuid',
            'user_id',
            'uuid',
            'id'
        );
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Position relationships
    public function employeePositions()
    {
        return $this->hasMany(EmployeePosition::class);
    }

    public function currentEmployees()
    {
        return $this->hasManyThrough(
            User::class,
            EmployeePosition::class,
            'department_id',
            'id',
            'id',
            'user_id'
        )->where('employee_positions.is_current', true);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithActiveProjects(Builder $query): Builder
    {
        return $query->whereHas('projects', function ($query) {
            $query->where('status', 'active');
        });
    }

    public function scopeWithActiveUsers(Builder $query): Builder
    {
        return $query->whereHas('users', function ($query) {
            $query->where('is_active', true);
        });
    }

    // Performance Metrics Methods
    public function getEfficiencyRate(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = $this->projects();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            return 0;
        }

        return $projects->avg(function ($project) {
            return $project->getEfficiencyRate();
        });
    }

    public function getCompletionRate(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = $this->workEntries();

        if ($startDate && $endDate) {
            $query->whereBetween('work_date', [$startDate, $endDate]);
        }

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $query->where('status', 'completed')->count();

        return ($completed / $total) * 100;
    }

    public function getTotalHours(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = $this->workEntries();

        if ($startDate && $endDate) {
            $query->whereBetween('work_date', [$startDate, $endDate]);
        }

        return $query->sum('hours_worked');
    }

    public function getActiveProjectsCount(): int
    {
        return $this->projects()
            ->where('status', 'active')
            ->count();
    }

    public function getActiveUsersCount(): int
    {
        return $this->users()
            ->where('is_active', true)
            ->count();
    }

    // Resource Utilization
    public function getResourceUtilization(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->projects();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $projects = $query->get();

        return [
            'allocated_hours' => $projects->sum('estimated_hours'),
            'actual_hours' => $projects->sum('actual_hours'),
            'utilization_rate' => $projects->sum('estimated_hours') > 0
              ? ($projects->sum('actual_hours') / $projects->sum('estimated_hours')) * 100
              : 0,
        ];
    }

    // Cross-Department Collaboration
    public function getCollaborationMetrics(): array
    {
        $sharedProjects = $this->projects()
            ->where('is_shared', true)
            ->get();

        $collaboratingDepartments = Department::whereHas('projects', function ($query) use ($sharedProjects) {
            $query->whereIn('id', $sharedProjects->pluck('id'));
        })
            ->where('uuid', '!=', $this->uuid)
            ->get();

        return [
            'collaborating_departments' => $collaboratingDepartments->pluck('name')->toArray(),
            'shared_projects' => $sharedProjects->count(),
            'collaboration_score' => $this->calculateCollaborationScore($sharedProjects->count(), $collaboratingDepartments->count()),
        ];
    }

    private function calculateCollaborationScore(int $sharedProjects, int $collaboratingDepts): float
    {
        if ($sharedProjects === 0 || $collaboratingDepts === 0) {
            return 0;
        }

        // Basic scoring algorithm - can be adjusted based on business needs
        $baseScore = (($sharedProjects * 10) + ($collaboratingDepts * 5));

        return min($baseScore, 100); // Cap at 100
    }

    public function updateResourceUtilization(): void
    {
        // Only update if projects table exists and has been migrated
        try {
            if (\Schema::hasTable('projects')) {
                $this->allocated_hours = $this->projects()->sum('estimated_hours');
                $this->actual_hours = $this->projects()->sum('actual_hours');
                $this->saveQuietly(); // Use saveQuietly to avoid triggering the booted event again
            }
        } catch (\Exception $e) {
            // Silently handle any database errors during migration/seeding
            \Log::info('Could not update resource utilization for department: '.$e->getMessage());
        }
    }

    protected static function booted()
    {
        static::saved(function ($department) {
            // Only update resource utilization if we're not in a migration/seeding context
            if (! \App::runningInConsole() || app()->environment('testing')) {
                $department->updateResourceUtilization();
            }
        });
    }
}
