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
            return '-';
        }

        $end = $this->logout_at ?? now();
        $diffSeconds = (int) $this->login_at->diffInSeconds($end);

        return sprintf(
            '%02d.%02d.%02d',
            intdiv($diffSeconds, 3600),
            intdiv($diffSeconds % 3600, 60),
            $diffSeconds % 60,
        );
    }
}
