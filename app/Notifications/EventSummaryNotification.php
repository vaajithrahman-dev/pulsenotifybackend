<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $storeId,
        public string $eventType,
        public string $summary,
        public string $eventId,
        public ?int $orderId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'store_id' => $this->storeId,
            'event_type' => $this->eventType,
            'event_id' => $this->eventId,
            'summary' => $this->summary,
            'order_id' => $this->orderId,
            'link' => $this->orderId ? "/app/orders/{$this->orderId}" : null,
        ];
    }
}

