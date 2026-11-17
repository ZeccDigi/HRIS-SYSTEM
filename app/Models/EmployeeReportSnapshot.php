<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeReportSnapshot extends Model
{
    protected $fillable = ['employee_id', 'snapshot_month', 'data'];

    protected function casts(): array
    {
        return [
            'snapshot_month' => 'date',
            'data' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
