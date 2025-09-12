<?php

use App\Jobs\SendReportEmailJob;
use App\Models\Report;
use App\Models\ReportDeliveryLog;
use App\Models\User;
use App\Services\Report\ReportDeliveryService;
use Illuminate\Support\Facades\Queue;

describe('ReportDeliveryService', function () {
    let('deliveryService', fn () => new ReportDeliveryService);
    let('user', fn () => User::factory()->create());
    let('report', fn () => Report::factory()->create(['user_id' => $this->user->id]));

    beforeEach(function () {
        Queue::fake();
    });

    describe('deliverReport', function () {
        it('creates delivery logs for each recipient', function () {
            $recipients = ['manager@company.com', 'hr@company.com'];

            $results = $this->deliveryService->deliverReport($this->report, [
                'recipients' => $recipients,
            ]);

            expect($results)->toHaveCount(2)
                ->and(ReportDeliveryLog::count())->toBe(2)
                ->and($this->report->fresh()->sent_at)->not->toBeNull();
        });

        it('queues email jobs for delivery', function () {
            $recipients = ['manager@company.com'];

            $this->deliveryService->deliverReport($this->report, [
                'recipients' => $recipients,
            ]);

            Queue::assertPushed(SendReportEmailJob::class, 1);
        });

        it('throws exception for empty recipients', function () {
            expect(fn () => $this->deliveryService->deliverReport($this->report, [
                'recipients' => [],
            ]))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('handleSuccessfulDelivery', function () {
        it('marks delivery as successful and sends notification', function () {
            $deliveryLog = ReportDeliveryLog::factory()->create([
                'report_id' => $this->report->id,
                'status' => 'pending',
            ]);

            $this->deliveryService->handleSuccessfulDelivery($deliveryLog, [
                'mail_id' => 'test-mail-id',
            ]);

            expect($deliveryLog->fresh()->status)->toBe('delivered')
                ->and($deliveryLog->fresh()->delivered_at)->not->toBeNull();
        });
    });

    describe('handleFailedDelivery', function () {
        it('marks delivery as failed and schedules retry if possible', function () {
            $deliveryLog = ReportDeliveryLog::factory()->create([
                'report_id' => $this->report->id,
                'status' => 'pending',
                'retry_count' => 1,
            ]);

            $this->deliveryService->handleFailedDelivery($deliveryLog, 'SMTP Error');

            expect($deliveryLog->fresh()->status)->toBe('failed')
                ->and($deliveryLog->fresh()->error_message)->toBe('SMTP Error')
                ->and($deliveryLog->fresh()->retry_count)->toBe(2);
        });

        it('does not schedule retry after max retries', function () {
            $deliveryLog = ReportDeliveryLog::factory()->create([
                'report_id' => $this->report->id,
                'status' => 'pending',
                'retry_count' => 3,
            ]);

            $this->deliveryService->handleFailedDelivery($deliveryLog, 'Final failure');

            expect($deliveryLog->fresh()->canRetry())->toBeFalse();
        });
    });

    describe('getDeliveryStats', function () {
        it('calculates correct delivery statistics', function () {
            // Create test delivery logs
            ReportDeliveryLog::factory()->count(3)->create([
                'report_id' => $this->report->id,
                'status' => 'delivered',
            ]);

            ReportDeliveryLog::factory()->count(2)->create([
                'report_id' => $this->report->id,
                'status' => 'failed',
            ]);

            $stats = $this->deliveryService->getDeliveryStats($this->report);

            expect($stats['total_recipients'])->toBe(5)
                ->and($stats['delivered'])->toBe(3)
                ->and($stats['failed'])->toBe(2)
                ->and($stats['success_rate'])->toBe(60.0);
        });
    });
});
