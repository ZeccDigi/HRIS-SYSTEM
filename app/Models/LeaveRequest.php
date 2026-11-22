<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\CarbonPeriod;

class LeaveRequest extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'date_filed',
        'reason',
        'leave_detail_type',
        'leave_detail',
        'attachment_path',
        'attachment_original_name',
        'status',
        'approved_by_chief',
        'chief_status',
        'chief_remarks',
        'approved_by_hrstaff',
        'hrstaff_status',
        'hrstaff_remarks',
        'approved_by_regionaldirector',
        'rd_status',
        'rd_remarks',
        'remarks',
        'is_paid',
        'commutation_requested',
        'is_monetization', 'monetization_basis', 'monetization_days', 'monetization_salary',
        'monetization_amount', 'monetization_factor', 'monetization_justification',
        'monetization_credit_before', 'monetization_credit_after',
        'monetization_verified_at', 'monetization_posted_at',
        'monetization_salary_grade', 'monetization_salary_step', 'monetization_salary_year',
    ];

    public function canBeEditedBy(?Employee $employee): bool
    {
        return $employee !== null
            && (int) $this->employee_id === (int) $employee->id
            && $this->status === 'pending'
            && in_array($this->hrstaff_status, [null, 'pending'], true)
            && $this->approved_by_hrstaff === null;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class)->withTrashed();
    }

    public function chief()
    {
        return $this->belongsTo(Employee::class, 'approved_by_chief');
    }

    public function hrstaff()
    {
        return $this->belongsTo(Employee::class, 'approved_by_hrstaff');
    }

    public function regionalDirector()
    {
        return $this->belongsTo(Employee::class, 'approved_by_regionaldirector');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'approved') {
            return $this->is_paid ? 'Approved with Pay' : 'Approved without Pay';
        }

        return ucfirst($this->status);
    }

    /**
     * Get the duration of the leave request excluding weekends and holidays.
     */
    public function getDurationAttribute(): float
    {
        if ($this->is_monetization && $this->monetization_days !== null) return (float) $this->monetization_days;
        if (!$this->start_date || !$this->end_date) return 0;
        return static::calculateBusinessDays($this->start_date, $this->end_date);
    }

    /**
     * Calculate business days (Mon-Fri) excluding holidays between two dates.
     */
    public static function calculateBusinessDays($startDate, $endDate): int
    {
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->startOfDay();
        
        if ($start->gt($end)) {
            return 0;
        }

        $holidays = \App\Models\Holiday::whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->toArray();

        return collect(CarbonPeriod::create($start, $end))
            ->filter(function (\Carbon\Carbon $date) use ($holidays) {
                return $date->isWeekday() && ! in_array($date->format('Y-m-d'), $holidays, true);
            })
            ->count();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_monetization' => 'boolean',
            'monetization_days' => 'decimal:3', 'monetization_salary' => 'decimal:2',
            'monetization_amount' => 'decimal:2', 'monetization_factor' => 'decimal:7',
            'monetization_credit_before' => 'decimal:3', 'monetization_credit_after' => 'decimal:3',
            'monetization_verified_at' => 'datetime', 'monetization_posted_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'date_filed' => 'datetime',
            'is_paid' => 'boolean',
            'commutation_requested' => 'boolean',
        ];
    }
}
