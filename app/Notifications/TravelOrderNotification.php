<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TravelOrderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public TravelOrder $travelOrder,
        public string $title,
        public string $message,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'travel_order',
            'title' => $this->title,
            'message' => $this->message,
            'travel_order_id' => $this->travelOrder->id,
            'action_url' => str_contains(strtolower($this->title), 'acknowledgement')
                ? route('travel-orders.ar.show', $this->travelOrder->id)
                : route('travel-orders.show', $this->travelOrder->id),
            'icon' => 'travel',
        ];
    }
}
