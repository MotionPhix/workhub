<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportEntry extends Model
{
    use BootableUuid, HasFactory;

    protected $fillable = [
        'report_id',
        'work_entry_id',
        'entry_date',
        'title',
        'description',
        'hours_worked',
        'metrics',
        'tags',
        'priority',
        'completion_status',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'hours_worked' => 'decimal:2',
        'metrics' => 'array',
        'tags' => 'array',
    ];

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function workEntry(): BelongsTo
    {
        return $this->belongsTo(WorkEntry::class);
    }

    // Scopes
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('completion_status', 'in_progress');
    }

    // Methods
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            'high' => 'bg-red-100 text-red-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getCompletionPercentage(): int
    {
        return match ($this->completion_status) {
            'completed' => 100,
            'in_progress' => 50,
            'pending' => 0,
            default => 0,
        };
    }
}
