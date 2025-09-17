<?php

namespace App\Services\Security;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportSecurityService
{
    private const ENCRYPTION_KEY_LENGTH = 32;

    private const AUDIT_LOG_RETENTION_DAYS = 365;

    public function encryptSensitiveData(array $data, array $sensitiveFields = []): array
    {
        $defaultSensitiveFields = [
            'revenue_generated',
            'pipeline_value',
            'ad_spend',
            'proposal_value',
            'client_feedback',
            'personal_notes',
        ];

        $fieldsToEncrypt = array_merge($defaultSensitiveFields, $sensitiveFields);

        foreach ($fieldsToEncrypt as $field) {
            if (isset($data[$field]) && ! empty($data[$field])) {
                try {
                    $data[$field] = Crypt::encrypt($data[$field]);
                    $data["_encrypted_{$field}"] = true; // Mark as encrypted
                } catch (\Exception $e) {
                    Log::error('Failed to encrypt sensitive data', [
                        'field' => $field,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $data;
    }

    public function decryptSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, '_encrypted_')) {
                $originalField = str_replace('_encrypted_', '', $key);

                if (isset($data[$originalField]) && $value === true) {
                    try {
                        $data[$originalField] = Crypt::decrypt($data[$originalField]);
                        unset($data["_encrypted_{$originalField}"]);
                    } catch (\Exception $e) {
                        Log::error('Failed to decrypt sensitive data', [
                            'field' => $originalField,
                            'error' => $e->getMessage(),
                        ]);
                        // Keep encrypted value if decryption fails
                    }
                }
            }
        }

        return $data;
    }

    public function auditReportAccess(Report $report, User $user, string $action, array $metadata = []): void
    {
        try {
            DB::table('report_audit_logs')->insert([
                'report_id' => $report->id,
                'user_id' => $user->id,
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'metadata' => json_encode(array_merge($metadata, [
                    'report_type' => $report->report_type->value,
                    'report_status' => $report->status,
                    'department' => $report->department?->name,
                ])),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'report_id' => $report->id,
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function validateReportAccess(Report $report, User $user, string $action): bool
    {
        // Log the access attempt
        $this->auditReportAccess($report, $user, "attempt_{$action}");

        $hasAccess = match ($action) {
            'view' => $this->canViewReport($report, $user),
            'edit' => $this->canEditReport($report, $user),
            'delete' => $this->canDeleteReport($report, $user),
            'approve' => $this->canApproveReport($report, $user),
            'send' => $this->canSendReport($report, $user),
            default => false,
        };

        if ($hasAccess) {
            $this->auditReportAccess($report, $user, "authorized_{$action}");
        } else {
            $this->auditReportAccess($report, $user, "denied_{$action}", [
                'reason' => 'insufficient_permissions',
            ]);

            Log::warning('Unauthorized report access attempt', [
                'user_id' => $user->id,
                'report_id' => $report->id,
                'action' => $action,
                'user_roles' => $user->getRoleNames()->toArray(),
            ]);
        }

        return $hasAccess;
    }

    private function canViewReport(Report $report, User $user): bool
    {
        // Owner can always view
        if ($report->user_id === $user->id) {
            return true;
        }

        // Managers can view their team's reports
        if ($user->hasRole('manager') && $report->user->manager_email === $user->email) {
            return true;
        }

        // Admins can view all reports
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Department heads can view reports from their department
        if ($user->hasRole('department-head') &&
            $report->department_id === $user->department_uuid) {
            return true;
        }

        return false;
    }

    private function canEditReport(Report $report, User $user): bool
    {
        // Only owner can edit draft reports
        if ($report->status === 'draft' && $report->user_id === $user->id) {
            return true;
        }

        // Admins can edit any report
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Managers can edit rejected reports from their team
        if ($user->hasRole('manager') &&
            $report->status === 'rejected' &&
            $report->user->manager_email === $user->email) {
            return true;
        }

        return false;
    }

    private function canDeleteReport(Report $report, User $user): bool
    {
        // Only owner can delete draft reports
        if ($report->status === 'draft' && $report->user_id === $user->id) {
            return true;
        }

        // Admins can delete any report
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return false;
    }

    private function canApproveReport(Report $report, User $user): bool
    {
        // Can't approve own reports
        if ($report->user_id === $user->id) {
            return false;
        }

        // Only pending reports can be approved
        if ($report->status !== 'pending') {
            return false;
        }

        // Managers can approve their team's reports
        if ($user->hasRole('manager') && $report->user->manager_email === $user->email) {
            return true;
        }

        // Department heads can approve reports from their department
        if ($user->hasRole('department-head') &&
            $report->department_id === $user->department_uuid) {
            return true;
        }

        // Admins can approve any report
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return false;
    }

    private function canSendReport(Report $report, User $user): bool
    {
        // Must be able to view the report first
        if (! $this->canViewReport($report, $user)) {
            return false;
        }

        // Report must be approved or user must be owner
        if ($report->status !== 'approved' && $report->user_id !== $user->id) {
            return false;
        }

        return true;
    }

    public function generateDataIntegrityHash(Report $report): string
    {
        $dataToHash = [
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'metrics_data' => $report->metrics_data,
            'content' => $report->content,
            'created_at' => $report->created_at->toISOString(),
        ];

        // Sort array keys for consistent hashing
        ksort($dataToHash);

        return hash('sha256', json_encode($dataToHash));
    }

    public function verifyDataIntegrity(Report $report, string $expectedHash): bool
    {
        $currentHash = $this->generateDataIntegrityHash($report);

        return hash_equals($expectedHash, $currentHash);
    }

    public function maskSensitiveDataForDisplay(array $data, User $viewer): array
    {
        // Don't mask for owner, managers, or admins
        if ($viewer->hasRole(['admin', 'super-admin', 'manager'])) {
            return $data;
        }

        $sensitiveFields = [
            'revenue_generated',
            'pipeline_value',
            'ad_spend',
            'proposal_value',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if (is_numeric($value)) {
                    // Show only the magnitude (e.g., "$10K-$50K" instead of "$27,543")
                    $data[$field] = $this->getMaskedRange($value);
                }
            }
        }

        return $data;
    }

    private function getMaskedRange(float $value): string
    {
        if ($value < 1000) {
            return '$'.number_format(floor($value / 100) * 100).'-$'.
                   number_format(ceil($value / 100) * 100);
        } elseif ($value < 10000) {
            return '$'.number_format(floor($value / 1000) * 1000, 0, '.', ',').'-$'.
                   number_format(ceil($value / 1000) * 1000, 0, '.', ',');
        } elseif ($value < 100000) {
            return '$'.number_format(floor($value / 10000) * 10, 0).'K-$'.
                   number_format(ceil($value / 10000) * 10, 0).'K';
        } else {
            return '$'.number_format(floor($value / 100000) * 100, 0).'K+';
        }
    }

    public function cleanupAuditLogs(): int
    {
        $cutoffDate = now()->subDays(self::AUDIT_LOG_RETENTION_DAYS);

        return DB::table('report_audit_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    public function getSecurityMetrics(array $filters = []): array
    {
        $query = DB::table('report_audit_logs');

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $logs = $query->get();

        return [
            'total_access_attempts' => $logs->count(),
            'authorized_accesses' => $logs->where('action', 'like', 'authorized_%')->count(),
            'denied_accesses' => $logs->where('action', 'like', 'denied_%')->count(),
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
            'most_accessed_reports' => $logs->groupBy('report_id')
                ->map->count()
                ->sortDesc()
                ->take(10)
                ->toArray(),
            'access_by_action' => $logs->groupBy('action')
                ->map->count()
                ->sortDesc()
                ->toArray(),
        ];
    }
}
