<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'days_per_year',
        'legal_basis',
        'is_active',
    ];
}
