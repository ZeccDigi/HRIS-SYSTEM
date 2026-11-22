<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCoreTimeSchedule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['core_time_schedule' => 'array', 'applies_from' => 'date', 'core_time_start_date' => 'date', 'core_time_end_date' => 'date'];
    }
}
