<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityAudit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'module',
        'description',
        'ip_address',
        'visit_token',
        'active_started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected $casts = [
        'active_started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function getPageDurationAttribute(): string
    {
        if ($this->duration_seconds === null) {
            return '-';
        }

        return sprintf('%02d:%02d:%02d.00', intdiv($this->duration_seconds, 3600), intdiv($this->duration_seconds % 3600, 60), $this->duration_seconds % 60);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
