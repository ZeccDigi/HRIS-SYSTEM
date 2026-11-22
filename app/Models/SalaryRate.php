<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRate extends Model
{
    protected $fillable = ['year', 'salary_grade', 'salary_step', 'monthly_salary', 'is_active'];
    protected function casts(): array
    {
        return ['year' => 'integer', 'monthly_salary' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
