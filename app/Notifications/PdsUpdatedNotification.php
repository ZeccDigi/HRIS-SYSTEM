<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PdsUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Employee $employee,
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
            'type' => 'pds_updated',
            'title' => $this->title,
            'message' => $this->message,
            'employee_id' => $this->employee->id,
            'action_url' => route('employees.pds.show', $this->employee->id),
            'icon' => 'pds',
        ];
    }
}
