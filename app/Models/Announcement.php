<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'is_published',
        'author_id',
        'audience_division',
        'published_at',
        'tags',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->whereNull('audience_division');
        }

        $role = strtolower((string) $user->role);
        if (in_array($role, ['admin', 'hrstaff', 'regionaldirector'], true)) {
            return $query;
        }

        $division = trim((string) $user->employee?->division);

        return $query->where(function ($query) use ($division) {
            $query->whereNull('audience_division');

            if ($division !== '') {
                $query->orWhere('audience_division', $division);
            }
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if (in_array(strtolower((string) $user->role), ['admin', 'hrstaff', 'regionaldirector'], true)) {
            return true;
        }

        return $this->audience_division === null
            || $this->audience_division === $user->employee?->division;
    }
}
