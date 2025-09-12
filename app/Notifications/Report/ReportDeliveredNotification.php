<?php

namespace App\Notifications\Report;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Report $report,
        public string $recipientEmail
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Report Delivered Successfully')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your report has been successfully delivered.')
            ->line('**Report:** '.$this->report->title)
            ->line('**Delivered to:** '.$this->recipientEmail)
            ->line('**Delivered at:** '.$this->report->sent_at->format('M j, Y g:i A'))
            ->action('View Report', route('reports.show', $this->report->id))
            ->line('Thank you for using our reporting system!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Report Delivered',
            'message' => "Your report '{$this->report->title}' was successfully delivered to {$this->recipientEmail}",
            'report_id' => $this->report->id,
            'action_url' => route('reports.show', $this->report->id),
            'type' => 'report_delivered',
        ];
    }
}
