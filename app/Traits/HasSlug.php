<?php

namespace App\Traits;

use App\Models\SlugRedirect;
use Illuminate\Support\Str;

/**
 * Lowercase, URL-safe, unique slugs generated from $slugSource (default
 * "title"). Admin-supplied slugs are normalised the same way and made
 * unique, so two records can never share a slug. When a slug changes on
 * an existing record the old one is recorded in slug_redirects so the
 * previous URL keeps working with a 301 (App\Services\SlugRedirectService).
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model): void {
            if (empty($model->{$model->slugSourceColumn()})) {
                return;
            }

            $model->slug = empty($model->slug)
                ? $model->generateUniqueSlug()
                : $model->generateUniqueSlug($model->slug);
        });

        static::updating(function ($model): void {
            if (! $model->isDirty('slug')) {
                return;
            }

            $original = $model->getOriginal('slug');

            if (empty($model->slug)) {
                $model->slug = $original ?: $model->generateUniqueSlug();
            } else {
                $model->slug = $model->generateUniqueSlug($model->slug);
            }

            if ($original && $original !== $model->slug) {
                $model->recordSlugRedirect($original, $model->slug);
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'title';
    }

    /**
     * Normalise a candidate (or the source column) and append -2, -3, ...
     * until it is unique among all rows of this model, including trashed
     * ones, so a restored record never collides.
     */
    protected function generateUniqueSlug(?string $candidate = null): string
    {
        $slug = Str::slug($candidate ?: $this->{$this->slugSourceColumn()});

        if ($slug === '') {
            $slug = Str::slug(Str::random(8));
        }

        $original = $slug;
        $count = 2;

        while ($this->slugExists($slug)) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::withoutGlobalScopes()->where('slug', $slug);

        if (method_exists($this, 'trashed')) {
            $query->withTrashed();
        }

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->exists();
    }

    /**
     * Keep old → new so inbound links survive. A redirect whose old slug is
     * now reused is removed to avoid loops, and existing redirects pointing
     * at the old slug are re-pointed at the new one (A→B, B→C ⇒ A→C).
     */
    protected function recordSlugRedirect(string $old, string $new): void
    {
        $type = $this->getMorphClass();

        SlugRedirect::query()->where('model_type', $type)->where('old_slug', $new)->delete();

        SlugRedirect::query()->where('model_type', $type)->where('new_slug', $old)->update(['new_slug' => $new]);

        SlugRedirect::query()->updateOrCreate(
            ['model_type' => $type, 'old_slug' => $old],
            ['new_slug' => $new],
        );
    }
}
