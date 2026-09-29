<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'balance',
        'earned',
        'used',
        'adjusted',
        'year',
        'accrued_as_of',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:3',
            'earned' => 'decimal:3',
            'used' => 'decimal:3',
            'adjusted' => 'decimal:3',
            'accrued_as_of' => 'date',
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

    public function transactions()
    {
        return $this->hasMany(LeaveCreditTransaction::class, 'leave_type_id', 'leave_type_id')
            ->where('employee_id', $this->employee_id);
    }
}
