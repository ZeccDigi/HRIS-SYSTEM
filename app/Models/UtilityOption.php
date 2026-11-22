<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UtilityOption extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'group_key',
        'label',
        'value',
        'parent_group',
        'parent_value',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForGroup(Builder $query, string $groupKey): Builder
    {
        return $query->where('group_key', $groupKey);
    }

    public static function listFor(string $groupKey, ?string $parentValue = null, ?string $currentValue = null): Collection
    {
        $query = static::query()
            ->active()
            ->forGroup($groupKey)
            ->orderBy('sort_order')
            ->orderBy('label');

        if ($parentValue !== null && $parentValue !== '') {
            $filtered = (clone $query)->where('parent_value', $parentValue)->get();
            $options = $filtered->isNotEmpty() ? $filtered : $query->get();
        } else {
            $options = $query->get();
        }

        if ($currentValue === null || $currentValue === '') {
            return $options;
        }

        $alreadyExists = $options->contains(function (UtilityOption $option) use ($currentValue) {
            return strcasecmp((string) $option->value, (string) $currentValue) === 0;
        });

        if ($alreadyExists) {
            return $options;
        }

        return collect([
            new static([
                'group_key' => $groupKey,
                'label' => $currentValue,
                'value' => $currentValue,
                'parent_value' => $parentValue,
                'is_active' => true,
                'sort_order' => -1,
            ]),
        ])->concat($options);
    }
}
