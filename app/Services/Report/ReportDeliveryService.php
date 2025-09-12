<?php

namespace App\Services\Report;

use App\Jobs\RetryFailedReportDeliveryJob;
use App\Jobs\SendReportEmailJob;
use App\Models\Report;
use App\Models\ReportDeliveryLog;
use App\Notifications\Report\ReportDeliveredNotification;
use App\Notifications\Report\ReportDeliveryFailedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportDeliveryService
{
    private const MAX_RETRIES = 3;

    private const RETRY_DELAYS = [5, 15, 60]; // minutes

    public function deliverReport(Report $report, array $options = []): array
    {
        $results = [];
        $recipients = $options['recipients'] ?? $report->recipient_emails ?? [];

        if (empty($recipients)) {
            throw new \InvalidArgumentException('No recipients specified for report delivery');
        }

        foreach ($recipients as $email) {
            $deliveryLog = $this->createDeliveryLog($report, $email, $options);

            try {
                $this->attemptDelivery($deliveryLog, $options);
                $results[] = ['email' => $email, 'status' => 'queued'];
            } catch (\Exception $e) {
                $deliveryLog->markAsFailed($e->getMessage());
                $results[] = ['email' => $email, 'status' => 'failed', 'error' => $e->getMessage()];

                Log::error('Report delivery failed', [
                    'report_id' => $report->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update report status
        $report->markAsSent(array_column($results, 'email'));

        return $results;
    }

    private function createDeliveryLog(Report $report, string $email, array $options): ReportDeliveryLog
    {
        return ReportDeliveryLog::create([
            'report_id' => $report->id,
            'recipient_email' => $email,
            'delivery_method' => $options['method'] ?? 'email',
            'status' => 'pending',
            'attempted_at' => now(),
            'retry_count' => 0,
            'metadata' => [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'delivery_options' => $options,
            ],
        ]);
    }

    private function attemptDelivery(ReportDeliveryLog $deliveryLog, array $options): void
    {
        $deliveryMethod = $options['method'] ?? 'email';

        switch ($deliveryMethod) {
            case 'email':
                SendReportEmailJob::dispatch($deliveryLog, $options)
                    ->onQueue('reports');
                break;

            case 'slack':
                // Future: Implement Slack delivery
                throw new \InvalidArgumentException('Slack delivery not yet implemented');
            case 'webhook':
                // Future: Implement webhook delivery
                throw new \InvalidArgumentException('Webhook delivery not yet implemented');
            default:
                throw new \InvalidArgumentException("Unsupported delivery method: {$deliveryMethod}");
        }
    }

    public function handleSuccessfulDelivery(ReportDeliveryLog $deliveryLog, array $metadata = []): void
    {
        DB::transaction(function () use ($deliveryLog, $metadata) {
            $deliveryLog->markAsDelivered();

            // Update metadata with delivery confirmation details
            $deliveryLog->update([
                'metadata' => array_merge($deliveryLog->metadata ?? [], $metadata, [
                    'delivered_at' => now()->toISOString(),
                ]),
            ]);

            // Notify user of successful delivery
            $deliveryLog->report->user->notify(
                new ReportDeliveredNotification($deliveryLog->report, $deliveryLog->recipient_email)
            );

            Log::info('Report delivered successfully', [
                'report_id' => $deliveryLog->report_id,
                'recipient' => $deliveryLog->recipient_email,
                'delivery_log_id' => $deliveryLog->id,
            ]);
        });
    }

    public function handleFailedDelivery(ReportDeliveryLog $deliveryLog, string $errorMessage, array $metadata = []): void
    {
        DB::transaction(function () use ($deliveryLog, $errorMessage, $metadata) {
            $deliveryLog->markAsFailed($errorMessage);

            // Update metadata with failure details
            $deliveryLog->update([
                'metadata' => array_merge($deliveryLog->metadata ?? [], $metadata, [
                    'failed_at' => now()->toISOString(),
                    'error_details' => $errorMessage,
                ]),
            ]);

            // Schedule retry if possible
            if ($deliveryLog->canRetry()) {
                $this->scheduleRetry($deliveryLog);
            } else {
                // Notify user of final failure
                $deliveryLog->report->user->notify(
                    new ReportDeliveryFailedNotification($deliveryLog->report, $deliveryLog->recipient_email, $errorMessage)
                );
            }

            Log::warning('Report delivery failed', [
                'report_id' => $deliveryLog->report_id,
                'recipient' => $deliveryLog->recipient_email,
                'retry_count' => $deliveryLog->retry_count,
                'error' => $errorMessage,
                'can_retry' => $deliveryLog->canRetry(),
            ]);
        });
    }

    private function scheduleRetry(ReportDeliveryLog $deliveryLog): void
    {
        $retryCount = $deliveryLog->retry_count;
        $delay = self::RETRY_DELAYS[$retryCount - 1] ?? 60; // Default to 60 minutes for additional retries

        RetryFailedReportDeliveryJob::dispatch($deliveryLog)
            ->delay(now()->addMinutes($delay))
            ->onQueue('reports-retry');

        Log::info('Report delivery retry scheduled', [
            'delivery_log_id' => $deliveryLog->id,
            'retry_count' => $retryCount,
            'delay_minutes' => $delay,
        ]);
    }

    public function retryFailedDelivery(ReportDeliveryLog $deliveryLog): bool
    {
        if (! $deliveryLog->canRetry()) {
            return false;
        }

        try {
            // Reset status and attempt delivery again
            $deliveryLog->update([
                'status' => 'pending',
                'attempted_at' => now(),
            ]);

            $this->attemptDelivery($deliveryLog, $deliveryLog->metadata['delivery_options'] ?? []);

            return true;
        } catch (\Exception $e) {
            $this->handleFailedDelivery($deliveryLog, $e->getMessage());

            return false;
        }
    }

    public function getDeliveryStats(Report $report): array
    {
        $logs = $report->deliveryLogs;

        return [
            'total_recipients' => $logs->count(),
            'delivered' => $logs->where('status', 'delivered')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'pending' => $logs->where('status', 'pending')->count(),
            'success_rate' => $logs->count() > 0
                ? round(($logs->where('status', 'delivered')->count() / $logs->count()) * 100, 2)
                : 0,
            'delivery_logs' => $logs->map(function ($log) {
                return [
                    'recipient' => $log->recipient_email,
                    'status' => $log->status,
                    'attempted_at' => $log->attempted_at,
                    'delivered_at' => $log->delivered_at,
                    'retry_count' => $log->retry_count,
                    'error_message' => $log->error_message,
                ];
            }),
        ];
    }

    public function getSystemDeliveryStats(array $filters = []): array
    {
        $query = ReportDeliveryLog::query();

        if (isset($filters['start_date'])) {
            $query->where('attempted_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('attempted_at', '<=', $filters['end_date']);
        }

        $logs = $query->get();

        return [
            'total_deliveries' => $logs->count(),
            'successful_deliveries' => $logs->where('status', 'delivered')->count(),
            'failed_deliveries' => $logs->where('status', 'failed')->count(),
            'pending_deliveries' => $logs->where('status', 'pending')->count(),
            'overall_success_rate' => $logs->count() > 0
                ? round(($logs->where('status', 'delivered')->count() / $logs->count()) * 100, 2)
                : 0,
            'average_retry_count' => $logs->avg('retry_count'),
            'common_errors' => $this->getCommonErrors($logs),
        ];
    }

    private function getCommonErrors($logs): array
    {
        return $logs->where('status', 'failed')
            ->whereNotNull('error_message')
            ->groupBy('error_message')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    public function cleanupOldDeliveryLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return ReportDeliveryLog::where('attempted_at', '<', $cutoffDate)
            ->where('status', '!=', 'pending')
            ->delete();
    }
}
