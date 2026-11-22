<?php

namespace App\Notifications;

use App\Models\CtoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CtoRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CtoRequest $ctoRequest,
        public string $title,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cto_request',
            'title' => \App\Support\NotificationTitle::format($this->title),
            'message' => $this->message,
            'cto_request_id' => $this->ctoRequest->id,
            'action_url' => route('my-cto.show', $this->ctoRequest->id),
            'icon' => 'cto',
        ];
    }
}
