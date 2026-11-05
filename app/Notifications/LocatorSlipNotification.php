<?php

namespace App\Notifications;

use App\Models\LocatorSlip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LocatorSlipNotification extends Notification
{
    use Queueable;

    public $locatorSlip;
    public $message;
    public $title;

    /**
     * Create a new notification instance.
     */
    public function __construct(LocatorSlip $locatorSlip, $title, $message)
    {
        $this->locatorSlip = $locatorSlip;
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Currently using database notifications
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $role = strtolower((string) ($notifiable->role ?? ''));
        $actionUrl = in_array($role, ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)
            ? route('hr.locator-slips.show', $this->locatorSlip->id)
            : route('locator-slips.show', $this->locatorSlip->id);

        return [
            'type' => 'locator_slip',
            'locator_slip_id' => $this->locatorSlip->id,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $actionUrl,
            'icon' => 'locator',
        ];
    }
}
