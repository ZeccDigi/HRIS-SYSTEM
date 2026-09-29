<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdsSectionReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_name',
        'status',
        'remarks',
        'reviewed_by_id',
        'changed_fields',
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];
}
