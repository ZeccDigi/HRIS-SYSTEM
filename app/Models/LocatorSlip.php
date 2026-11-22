<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocatorSlip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date_covered',
        'destination',
        'purpose',
        'type',
        'time_from',
        'out_scanned_at',
        'time_to',
        'status',
        'chief_remarks',
        'recommending_approval_id',
        'approved_by_id',
    ];

    protected function casts(): array
    {
        return [
            'date_covered' => 'date',
            'out_scanned_at' => 'datetime',
        ];
    }

    public function canBeEditedBy(?Employee $employee): bool
    {
        return $employee !== null
            && (int) $this->employee_id === (int) $employee->id
            && $this->status === 'pending'
            && in_array($this->chief_approval_status, [null, 'pending'], true)
            && $this->approved_by_chief_id === null
            && $this->out_scanned_at === null
            && $this->time_from === null;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recommendingApproval()
    {
        return $this->belongsTo(User::class, 'recommending_approval_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}

