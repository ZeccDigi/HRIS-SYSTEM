<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonPeriod;

class LeaveRequest extends Model
{
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
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
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
    public function getDurationAttribute(): int
    {
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
            'start_date' => 'date',
            'end_date' => 'date',
            'date_filed' => 'datetime',
            'is_paid' => 'boolean',
            'commutation_requested' => 'boolean',
        ];
    }
}
