<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model): void {
            if (empty($model->{$model->slugSourceColumn()})) {
                return;
            }

            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'title';
    }

    protected function generateUniqueSlug(): string
    {
        $source = $this->{$this->slugSourceColumn()};
        $slug = Str::slug($source);
        $original = $slug;
        $count = 1;

        while (static::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }
}
