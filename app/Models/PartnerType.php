<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerType extends Model
{
    use HasSlug;

    protected string $slugSource = 'name';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order_column' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column')->orderBy('name');
    }
}
