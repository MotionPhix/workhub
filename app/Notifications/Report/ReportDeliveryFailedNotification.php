<?php

namespace App\Notifications\Report;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportDeliveryFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Report $report,
        public string $recipientEmail,
        public string $errorMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Report Delivery Failed')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('We were unable to deliver your report after multiple attempts.')
            ->line('**Report:** '.$this->report->title)
            ->line('**Intended recipient:** '.$this->recipientEmail)
            ->line('**Error:** '.$this->errorMessage)
            ->line('Please check the recipient email address and try sending the report again.')
            ->action('View Report', route('reports.show', $this->report->id))
            ->line('If this problem persists, please contact support.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Report Delivery Failed',
            'message' => "Failed to deliver report '{$this->report->title}' to {$this->recipientEmail}. Error: {$this->errorMessage}",
            'report_id' => $this->report->id,
            'action_url' => route('reports.show', $this->report->id),
            'type' => 'report_delivery_failed',
            'priority' => 'high',
        ];
    }
}
