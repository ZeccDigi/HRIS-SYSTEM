<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CtoRequest extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'employee_id',
        'type',
        'date_start',
        'date_end',
        'hours',
        'verified_overtime_hours',
        'purpose',
        'overtime_authority_reference',
        'exception_justification',
        'attachment_path',
        'attachment_original_name',
        'applicant_signature_path',
        'cto_balance_before',
        'cto_balance_after',
        'credits_posted_at',
        'status',
        'approved_by_chief',
        'chief_status',
        'chief_remarks',
        'approved_by_hrstaff',
        'hrstaff_status',
        'hrstaff_remarks',
        'hr_verified_at',
        'approved_by_regionaldirector',
        'rd_status',
        'rd_remarks',
        'credit_source', 'recorded_by', 'issued_on', 'valid_until', 'grant_remaining',
        'rendered_hours', 'coc_rate', 'overtime_classification', 'coc_certificate_id',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'date_end' => 'date',
            'hours' => 'decimal:2',
            'verified_overtime_hours' => 'decimal:2',
            'hr_verified_at' => 'datetime',
            'cto_balance_before' => 'decimal:2',
            'cto_balance_after' => 'decimal:2',
            'credits_posted_at' => 'datetime',
            'issued_on' => 'date',
            'valid_until' => 'date',
            'grant_remaining' => 'decimal:2',
            'rendered_hours' => 'decimal:2', 'coc_rate' => 'decimal:2',
        ];
    }

    public function canBeEditedBy(?Employee $employee): bool
    {
        return $employee !== null
            && (int) $this->employee_id === (int) $employee->id
            && $this->status === 'pending'
            && in_array($this->hrstaff_status, [null, 'pending'], true)
            && $this->approved_by_hrstaff === null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chief(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_chief');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function hrstaff(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_hrstaff');
    }

    public function regionalDirector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_regionaldirector');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earn' => 'Earn COC',
            'use' => 'Use CTO',
            default => ucfirst($this->type),
        };
    }

    public function getReferenceAttribute(): string
    {
        $date = $this->date_start ?? $this->created_at;

        return sprintf('CTO-%s-%04d', $date?->format('Y') ?? now()->format('Y'), $this->id);
    }
}
