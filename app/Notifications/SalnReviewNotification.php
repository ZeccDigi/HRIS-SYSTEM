<?php

namespace App\Notifications;

use App\Models\Saln;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SalnReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Saln $saln,
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'saln_review',
            'title' => $this->title,
            'message' => $this->message,
            'saln_id' => $this->saln->id,
            'employee_id' => $this->saln->employee_id,
            'action_url' => $this->actionUrl ?: route('salns.show', $this->saln),
            'icon' => 'saln',
        ];
    }
}
