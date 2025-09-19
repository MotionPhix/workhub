<?php

namespace App\States;

use Thunk\Verbs\State;

class NotificationState extends State
{
    public int $userId;
    public array $notifications = [];
    public array $preferences = [];
    public array $statistics = [
        'total_sent' => 0,
        'total_read' => 0,
        'total_clicked' => 0,
        'channels_used' => []
    ];

    public function addNotification(array $notification): void
    {
        $this->notifications[$notification['id']] = $notification;
        $this->updateStatistics('sent');
    }

    public function markAsRead(string $notificationId): void
    {
        if (isset($this->notifications[$notificationId])) {
            $this->notifications[$notificationId]['read_at'] = now()->toISOString();
            $this->notifications[$notificationId]['status'] = 'read';
            $this->updateStatistics('read');
        }
    }

    public function markAsClicked(string $notificationId): void
    {
        if (isset($this->notifications[$notificationId])) {
            $this->notifications[$notificationId]['clicked_at'] = now()->toISOString();
            $this->notifications[$notificationId]['status'] = 'clicked';
            $this->updateStatistics('clicked');
        }
    }

    public function updatePreferences(array $preferences): void
    {
        $this->preferences = array_merge($this->preferences, $preferences);
    }

    public function getUnreadNotifications(): array
    {
        return array_filter($this->notifications, function ($notification) {
            return !isset($notification['read_at']);
        });
    }

    public function getNotificationsByType(string $type): array
    {
        return array_filter($this->notifications, function ($notification) use ($type) {
            return $notification['type'] === $type;
        });
    }

    public function markAsSent(string $notificationId, string $channel): void
    {
        if (isset($this->notifications[$notificationId])) {
            $this->notifications[$notificationId]['sent_at'] = now()->toISOString();
            $this->notifications[$notificationId]['status'] = 'sent';

            if (!isset($this->notifications[$notificationId]['sent_channels'])) {
                $this->notifications[$notificationId]['sent_channels'] = [];
            }
            $this->notifications[$notificationId]['sent_channels'][] = $channel;
            $this->updateChannelStatistics($channel);
        }
    }

    public function markAsFailed(string $notificationId, string $channel, ?string $errorMessage = null): void
    {
        if (isset($this->notifications[$notificationId])) {
            $this->notifications[$notificationId]['failed_at'] = now()->toISOString();
            $this->notifications[$notificationId]['status'] = 'failed';
            $this->notifications[$notificationId]['error_message'] = $errorMessage;

            if (!isset($this->notifications[$notificationId]['failed_channels'])) {
                $this->notifications[$notificationId]['failed_channels'] = [];
            }
            $this->notifications[$notificationId]['failed_channels'][] = $channel;
        }
    }

    private function updateStatistics(string $action): void
    {
        switch ($action) {
            case 'sent':
                $this->statistics['total_sent']++;
                break;
            case 'read':
                $this->statistics['total_read']++;
                break;
            case 'clicked':
                $this->statistics['total_clicked']++;
                break;
        }
    }

    private function updateChannelStatistics(string $channel): void
    {
        if (!isset($this->statistics['channels_used'][$channel])) {
            $this->statistics['channels_used'][$channel] = 0;
        }
        $this->statistics['channels_used'][$channel]++;
    }
}
