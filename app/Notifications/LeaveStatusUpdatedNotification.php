<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public LeaveRequest $leaveRequest, public ?string $statusText = null) {}

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
        $status = $this->leaveRequest->status;
        $kind = $this->leaveRequest->is_monetization ? 'Monetization request' : 'Leave request';
        $description = $this->leaveRequest->is_monetization
            ? 'monetization of leave credits'
            : ($this->leaveRequest->leaveType?->name ?? 'leave').' ('.$this->leaveRequest->start_date?->format('M d').' - '.$this->leaveRequest->end_date?->format('M d, Y').')';
        $pending = $status === 'pending';

        return [
            'type' => 'leave_status',
            'title' => $pending ? $kind.' forwarded for approval' : $kind.' '.$status,
            'message' => $pending ? "Your request for {$description} has been verified and forwarded for further approval." : "Your request for {$description} has been {$status}.",
            'leave_request_id' => $this->leaveRequest->id,
            'action_url' => route('leaves.show', $this->leaveRequest->id),
            'status' => $this->leaveRequest->status,
            'icon' => $this->leaveRequest->status === 'approved' ? 'check' : 'x',
        ];
    }
}
