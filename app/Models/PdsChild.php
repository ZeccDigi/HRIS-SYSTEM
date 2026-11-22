<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdsChild extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = ['date_of_birth' => 'date:Y-m-d'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
