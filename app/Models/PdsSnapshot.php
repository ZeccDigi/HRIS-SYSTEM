<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdsSnapshot extends Model
{
    use SoftDeletes;
    protected $fillable = ['employee_id', 'year', 'data', 'draft_data', 'reviews', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'data' => 'array',
            'draft_data' => 'array',
            'reviews' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
