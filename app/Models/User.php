<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'privacy_consent',
        'privacy_consented_at',
        'is_approved',
        'must_change_password',
        'account_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'privacy_consented_at' => 'datetime',
            'password' => 'hashed',
            'privacy_consent' => 'boolean',
            'is_approved' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Check if the OTP has expired.
     */
    public function isOtpExpired(): bool
    {
        return ! $this->otp_expires_at || $this->otp_expires_at->isPast();
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->employee?->notification_email;
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        return $this->employee?->profile_picture_url;
    }

    public function getInitialsAttribute(): string
    {
        if ($this->employee) {
            return $this->employee->initials;
        }

        return strtoupper(substr($this->display_name, 0, 1)) ?: '?';
    }

    /**
     * Get the user's display name. Prioritizes the bound Employee's name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->employee) {
            $first = $this->employee->firstname;
            $last = $this->employee->lastname;

            return trim("$first $last") ?: $this->name;
        }

        return $this->name;
    }

    /**
     * Get the user's role.
     * Default to 'user' if no employee record exists.
     */
    public function getRoleAttribute(): string
    {
        return $this->employee?->account_role ?? 'user';
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }

        return $this->role === $roles;
    }
}
