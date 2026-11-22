<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LeaveCreditAdjustedNotification extends Notification
{
    public function __construct(
        public string $leaveType,
        public string $operation,
        public float $amount,
        public float $balance,
        public int $year,
        public ?string $remarks = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $action = $this->operation === 'deduct' ? 'deducted' : 'added';
        return [
            'type' => 'leave_credit_adjustment', 'icon' => 'leave',
            'title' => 'Leave credits adjusted by HR',
            'message' => 'HR '.$action.' '.number_format($this->amount, 3).' day(s) '.($this->operation === 'deduct' ? 'from' : 'to').' your '.$this->leaveType.' credits for '.$this->year.'. New balance: '.number_format($this->balance, 3).' day(s).'.(filled($this->remarks) ? ' Remarks: '.$this->remarks : ''),
            'action_url' => route('leaves.index', ['tab' => 'credits']),
            'operation' => $this->operation, 'amount' => $this->amount,
            'balance_after' => $this->balance, 'year' => $this->year,
        ];
    }
}
