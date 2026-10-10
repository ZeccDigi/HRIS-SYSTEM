<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;

class Saln extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'type_of_filing',
        'as_of_date',
        'declarant_info',
        'spouse_info',
        'filing_status',
        'children',
        'real_properties',
        'personal_properties',
        'liabilities',
        'has_business_interests',
        'business_interests',
        'has_relatives_in_gov',
        'relatives_in_gov',
        'total_assets',
        'total_liabilities',
        'net_worth',
        'review_status',
        'review_remarks',
        'reviewed_by_id',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'declarant_info' => 'array',
            'spouse_info' => 'array',
            'children' => 'array',
            'real_properties' => 'array',
            'personal_properties' => 'array',
            'liabilities' => 'array',
            'has_business_interests' => 'boolean',
            'business_interests' => 'array',
            'has_relatives_in_gov' => 'boolean',
            'relatives_in_gov' => 'array',
            'total_assets' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'net_worth' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
