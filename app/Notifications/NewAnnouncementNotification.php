<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Announcement;

class NewAnnouncementNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Announcement $announcement) {}

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
            'type' => 'announcement',
            'title' => $this->announcement->tags === 'Office Orders' ? 'Office Order' : 'Official Announcement',
            'message' => "{$this->announcement->author->name} posted a new announcement: {$this->announcement->title}",
            'announcement_id' => $this->announcement->id,
            'action_url' => route('announcements.show', $this->announcement),
            'icon' => 'announcement',
        ];
    }
}
