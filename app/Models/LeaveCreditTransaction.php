<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveCreditTransaction extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_request_id',
        'type',
        'amount',
        'balance_after',
        'effective_date',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'effective_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
