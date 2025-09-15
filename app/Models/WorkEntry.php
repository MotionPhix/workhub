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
    use BootUuid, HasFactory, HasTags;

    protected $fillable = [
        'user_id',
        'project_uuid',
        'work_title',
        'description',
        'notes',
        'start_date_time',
        'end_date_time',
        'status',
        'priority',
        'work_type',
        'location',
        'contacts',
        'organizations',
        'value_generated',
        'outcome',
        'attachments',
        'mood',
        'productivity_rating',
        'tools_used',
        'collaborators',
        'requires_follow_up',
        'follow_up_date',
        'weather_condition',
    ];

    protected $casts = [
        'start_date_time' => 'datetime',
        'end_date_time' => 'datetime',
        'follow_up_date' => 'date',
        'description' => CleanHtmlInput::class,
        'contacts' => 'array',
        'organizations' => 'array',
        'attachments' => 'array',
        'tools_used' => 'array',
        'collaborators' => 'array',
        'value_generated' => 'decimal:2',
        'productivity_rating' => 'decimal:1',
        'requires_follow_up' => 'boolean',
    ];

    protected $appends = ['hours_worked', 'tag_names'];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_uuid', 'uuid');
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProject(Builder $query, string $projectUuid): Builder
    {
        return $query->where('project_uuid', $projectUuid);
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
        return $query->whereBetween('start_date_time', [$startDate, $endDate]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('start_date_time', Carbon::now()->month);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByWorkType(Builder $query, string $workType): Builder
    {
        return $query->where('work_type', $workType);
    }

    public function scopeRequiresFollowUp(Builder $query): Builder
    {
        return $query->where('requires_follow_up', true);
    }

    public function scopeOverdueFollowUp(Builder $query): Builder
    {
        return $query->where('requires_follow_up', true)
            ->where('follow_up_date', '<', Carbon::now()->toDateString());
    }

    public function scopeWithValue(Builder $query): Builder
    {
        return $query->whereNotNull('value_generated')
            ->where('value_generated', '>', 0);
    }

    // Computed properties
    public function getHoursWorkedAttribute(): float
    {
        if (! $this->start_date_time || ! $this->end_date_time) {
            return 0;
        }

        $start = Carbon::parse($this->start_date_time);
        $end = Carbon::parse($this->end_date_time);

        if ($end <= $start) {
            return 0;
        }

        return round($end->diffInHours($start, true), 2);
    }

    public function getTagNamesAttribute(): array
    {
        return $this->tags->map(function ($tag) {
            // Extract the name from JSON structure, default to 'en' locale
            $name = $tag->name;
            if (is_array($name)) {
                return $name['en'] ?? $name[array_key_first($name)] ?? $tag->slug['en'] ?? '';
            }

            return $name;
        })->toArray();
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray'
        };
    }

    public function getWorkTypeIconAttribute(): string
    {
        return match ($this->work_type) {
            'meeting' => 'users',
            'call' => 'phone',
            'email' => 'mail',
            'travel' => 'car',
            'research' => 'search',
            'presentation' => 'presentation',
            'task' => 'check-square',
            default => 'activity'
        };
    }

    public function getProductivityLevelAttribute(): string
    {
        if (! $this->productivity_rating) {
            return 'Not Rated';
        }

        return match (true) {
            $this->productivity_rating >= 4.5 => 'Excellent',
            $this->productivity_rating >= 3.5 => 'Good',
            $this->productivity_rating >= 2.5 => 'Average',
            $this->productivity_rating >= 1.5 => 'Below Average',
            default => 'Poor'
        };
    }

    // Methods
    public function updateEntry(array $attributes): bool
    {
        return DB::transaction(function () use ($attributes) {
            if (isset($attributes['start_date_time'])) {
                $attributes['start_date_time'] = Carbon::parse($attributes['start_date_time']);
            }

            if (isset($attributes['end_date_time'])) {
                $attributes['end_date_time'] = Carbon::parse($attributes['end_date_time']);
            }

            if (isset($attributes['tags'])) {
                $tags = $attributes['tags'];
                unset($attributes['tags']);
                $this->syncTags($tags);
            }

            $updated = $this->update(array_filter($attributes));

            if ($updated && $this->project_uuid) {
                $this->project->updateProgress();
            }

            return $updated;
        });
    }

    protected static function booted()
    {
        static::created(function ($workEntry) {
            if ($workEntry->project_uuid) {
                $workEntry->project->updateProgress();
            }
        });

        static::updated(function ($workEntry) {
            if ($workEntry->project_uuid) {
                $workEntry->project->updateProgress();
            }
        });
    }
}
