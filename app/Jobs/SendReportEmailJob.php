<?php

namespace App\Jobs;

use App\Mail\ReportEmail;
use App\Models\ReportDeliveryLog;
use App\Services\Report\ReportDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReportEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // We handle retries manually

    public int $maxExceptions = 1;

    public int $timeout = 120; // 2 minutes

    public function __construct(
        public ReportDeliveryLog $deliveryLog,
        public array $options = []
    ) {}

    public function handle(ReportDeliveryService $deliveryService): void
    {
        try {
            $report = $this->deliveryLog->report;
            $recipient = $this->deliveryLog->recipient_email;

            // Generate PDF if not already generated
            if (! $report->getFirstMedia('generated_pdf')) {
                app('App\Services\Report\ReportPdfGenerationService')->generatePdf($report);
            }

            // Send the email
            $mailable = new ReportEmail($report, $this->options);
            Mail::to($recipient)->send($mailable);

            // Mark as delivered
            $deliveryService->handleSuccessfulDelivery($this->deliveryLog, [
                'mail_id' => $mailable->getMessageId() ?? null,
                'sent_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            $deliveryService->handleFailedDelivery(
                $this->deliveryLog,
                $e->getMessage(),
                [
                    'exception_class' => get_class($e),
                    'exception_line' => $e->getLine(),
                    'exception_file' => $e->getFile(),
                ]
            );

            // Don't throw exception to prevent job retry - we handle it manually
            Log::error('SendReportEmailJob failed', [
                'delivery_log_id' => $this->deliveryLog->id,
                'recipient' => $this->deliveryLog->recipient_email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('SendReportEmailJob completely failed', [
            'delivery_log_id' => $this->deliveryLog->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        app(ReportDeliveryService::class)->handleFailedDelivery(
            $this->deliveryLog,
            'Job failed completely: '.$exception->getMessage()
        );
    }
}
