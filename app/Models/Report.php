<?php

namespace App\Models;

use App\Enums\ReportType;
use App\Traits\BootableUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Report extends Model implements HasMedia
{
    use BootableUuid, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'report_type',
        'title',
        'period_start',
        'period_end',
        'metrics_data',
        'content',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'sent_at',
        'delivery_status',
        'recipient_emails',
        'template_version',
        'settings',
    ];

    protected $casts = [
        'report_type' => ReportType::class,
        'period_start' => 'date',
        'period_end' => 'date',
        'metrics_data' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipient_emails' => 'array',
        'settings' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reportEntries(): HasMany
    {
        return $this->hasMany(ReportEntry::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(ReportDeliveryLog::class);
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'text/csv', 'application/vnd.ms-excel']);

        $this->addMediaCollection('generated_pdf')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    // Scopes
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate])
            ->orWhereBetween('period_end', [$startDate, $endDate]);
    }

    public function scopeByType($query, ReportType $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    // Methods
    public function generateTitle(): string
    {
        $periodText = $this->period_start->format('M d').' - '.$this->period_end->format('M d, Y');

        return $this->report_type->getDisplayName().' - '.$periodText;
    }

    public function getRequiredMetrics(): array
    {
        return $this->report_type->getRequiredFields();
    }

    public function isComplete(): bool
    {
        if (! $this->metrics_data) {
            return false;
        }

        $requiredFields = $this->getRequiredMetrics();
        $providedFields = array_keys($this->metrics_data);

        return empty(array_diff($requiredFields, $providedFields));
    }

    public function calculateCompletionPercentage(): int
    {
        if (! $this->metrics_data) {
            return 0;
        }

        $requiredFields = $this->getRequiredMetrics();
        $providedFields = array_keys(array_filter($this->metrics_data, fn ($value) => ! is_null($value) && $value !== ''));

        $completed = count(array_intersect($requiredFields, $providedFields));
        $total = count($requiredFields);

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    public function markAsSent(array $recipientEmails): void
    {
        $this->update([
            'sent_at' => now(),
            'delivery_status' => 'sent',
            'recipient_emails' => $recipientEmails,
        ]);
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);
    }

    public function reject(User $approver, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'settings' => array_merge($this->settings ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]),
        ]);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'sent' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
