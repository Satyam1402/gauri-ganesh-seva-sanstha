<?php

namespace App\Models;

use App\Enums\FaqStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Faq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'faq_category_id',
        'question',
        'answer',
        'status',
        'is_featured',
        'display_order',
        'published_at',
        'admin_notes',
        'created_by',
    ];

    /**
     * Private fields that must never leak through toArray()/JSON.
     */
    protected $hidden = [
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => FaqStatus::class,
            'is_featured' => 'boolean',
            'display_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Everything the public website may show: published and past its
     * publish date (a future date schedules it, like blog posts). FAQs in
     * an archived (inactive) category are hidden along with the category.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FaqStatus::Published->value)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('faq_category_id')
                ->orWhereHas('category', fn (Builder $c) => $c->where('is_active', true)));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Default public ordering: admin display order first, then newest.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Match the search term against question and answer. MySQL uses the
     * FULLTEXT index; other drivers (SQLite in tests) fall back to LIKE.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        if ($query->getConnection()->getDriverName() === 'mysql' && mb_strlen($term) >= 3) {
            return $query->whereFullText(['question', 'answer'], $term);
        }

        return $query->where(fn (Builder $q) => $q->where('question', 'like', "%{$term}%")
            ->orWhere('answer', 'like', "%{$term}%"));
    }

    public function isPublished(): bool
    {
        return $this->status === FaqStatus::Published;
    }

    public function isScheduled(): bool
    {
        return $this->isPublished() && $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * Answer rendered from Markdown with raw HTML stripped and unsafe
     * links disallowed — the same safe mode the blog uses, so admins get
     * lists/links/emphasis without any XSS surface.
     */
    public function answerHtml(): string
    {
        return Str::markdown($this->answer, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Plain-text answer for structured data and excerpts.
     */
    public function answerText(): string
    {
        return trim(html_entity_decode(strip_tags($this->answerHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Stable in-page anchor for deep links.
     */
    public function anchor(): string
    {
        return 'faq-'.$this->id;
    }
}
