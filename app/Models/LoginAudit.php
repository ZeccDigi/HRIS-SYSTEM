<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'status',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'os',
        'login_method',
        'failure_reason',
        'login_at',
        'logout_at',
        'session_id',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    protected $appends = [
        'duration',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationAttribute(): string
    {
        if (! $this->login_at) {
            return 'N/A';
        }

        $end = $this->logout_at ?? now();
        $diffSeconds = (int) $this->login_at->diffInSeconds($end);

        if ($diffSeconds < 60) {
            return $this->logout_at ? '< 1 min' : 'Active (< 1 min)';
        }

        $hours = (int) floor($diffSeconds / 3600);
        $minutes = (int) floor(($diffSeconds % 3600) / 60);

        $timeStr = '';
        if ($hours > 0) {
            $timeStr .= $hours . ($hours == 1 ? ' hr ' : ' hrs ');
        }
        $timeStr .= $minutes . ($minutes == 1 ? ' min' : ' mins');

        return $this->logout_at ? trim($timeStr) : 'Active (' . trim($timeStr) . ')';
    }
}
