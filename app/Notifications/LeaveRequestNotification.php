<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public LeaveRequest $leaveRequest) {}

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
            'type' => 'leave_request',
            'title' => 'New leave request',
            'message' => "{$this->leaveRequest->employee->user->name} submitted a {$this->leaveRequest->leaveType->name} for {$this->leaveRequest->start_date->format('M d')} to {$this->leaveRequest->end_date->format('M d, Y')}.",
            'leave_request_id' => $this->leaveRequest->id,
            'action_url' => route('leave-applications.show', $this->leaveRequest->id),
            'icon' => 'leave',
        ];
    }
}
