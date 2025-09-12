<?php

namespace App\Jobs;

use App\Models\ReportDeliveryLog;
use App\Services\Report\ReportDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedReportDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public ReportDeliveryLog $deliveryLog) {}

    public function handle(ReportDeliveryService $deliveryService): void
    {
        // Check if the delivery log still exists and can be retried
        if (! $this->deliveryLog->exists || ! $this->deliveryLog->canRetry()) {
            Log::info('Retry job skipped - delivery log no longer eligible for retry', [
                'delivery_log_id' => $this->deliveryLog->id,
                'exists' => $this->deliveryLog->exists,
                'can_retry' => $this->deliveryLog->canRetry(),
            ]);

            return;
        }

        Log::info('Retrying failed report delivery', [
            'delivery_log_id' => $this->deliveryLog->id,
            'recipient' => $this->deliveryLog->recipient_email,
            'retry_count' => $this->deliveryLog->retry_count,
        ]);

        $deliveryService->retryFailedDelivery($this->deliveryLog);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RetryFailedReportDeliveryJob failed', [
            'delivery_log_id' => $this->deliveryLog->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
