<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdsWorkExperience extends Model
{
    use HasFactory;

    protected $table = 'pds_work_experiences';
    protected $guarded = [];

    protected $casts = ['is_gov_service' => 'integer'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
