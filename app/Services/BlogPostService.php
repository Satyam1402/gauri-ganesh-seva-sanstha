<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Interfaces\BlogPostRepositoryInterface;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Repositories\BlogCategoryRepository;
use App\Repositories\BlogPostRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlogPostService
{
    /**
     * Average adult reading speed used for the reading-time estimate.
     */
    private const WORDS_PER_MINUTE = 200;

    public function __construct(private BlogPostRepositoryInterface $posts) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPost(array $data, int $authorId): BlogPost
    {
        $post = $this->posts->create($this->postAttributes($data, authorId: $authorId));

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $post->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        }

        $this->syncGallery($post, $data['gallery'] ?? [], []);
        $this->syncTags($post, $data['tags'] ?? '');
        $this->syncSeo($post, $data);

        $this->forgetCache();

        return $post->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePost(BlogPost $post, array $data): BlogPost
    {
        $this->posts->update($post, $this->postAttributes($data, post: $post));

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $post->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        } elseif (! empty($data['remove_featured_image'])) {
            $post->clearMediaCollection('featured_image');
        }

        $this->syncGallery($post, $data['gallery'] ?? [], $data['remove_gallery_ids'] ?? []);
        $this->syncTags($post, $data['tags'] ?? '');
        $this->syncSeo($post, $data);

        $this->forgetCache();

        return $post->refresh();
    }

    public function deletePost(BlogPost $post): bool
    {
        $deleted = (bool) $post->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restorePost(BlogPost $post): BlogPost
    {
        $post->restore();
        $this->forgetCache();

        return $post;
    }

    public function toggleFeatured(BlogPost $post): BlogPost
    {
        $this->posts->update($post, ['is_featured' => ! $post->is_featured]);
        $this->forgetCache();

        return $post;
    }

    /**
     * Publish now — a missing publish date is backfilled so the post is
     * immediately live rather than stuck as "scheduled with no date".
     */
    public function publish(BlogPost $post): BlogPost
    {
        $this->posts->update($post, [
            'status' => PostStatus::Published->value,
            'published_at' => $post->published_at ?? now(),
        ]);
        $this->forgetCache();

        return $post;
    }

    public function unpublish(BlogPost $post): BlogPost
    {
        $this->posts->update($post, ['status' => PostStatus::Draft->value]);
        $this->forgetCache();

        return $post;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->posts->bulkDelete($ids);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkPublish(array $ids): int
    {
        $count = $this->posts->bulkUpdateStatus($ids, PostStatus::Published->value);

        // Backfill missing publish dates the same way single publish does.
        BlogPost::whereIn('id', $ids)->whereNull('published_at')->update(['published_at' => now()]);

        $this->forgetCache();

        return $count;
    }

    /**
     * Word-count based estimate, never below one minute.
     */
    public function estimateReadingMinutes(string $content): int
    {
        $words = str_word_count(strip_tags($content));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    public function forgetCache(): void
    {
        foreach ([3, 5, 6, 9] as $limit) {
            Cache::forget(BlogPostRepository::CACHE_PREFIX.".latest.{$limit}");
            Cache::forget(BlogPostRepository::CACHE_PREFIX.".featured.{$limit}");
            Cache::forget(BlogPostRepository::CACHE_PREFIX.".popular.{$limit}");
        }

        // Category sidebar shows published-post counts, so it must refresh too.
        Cache::forget(BlogCategoryRepository::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function postAttributes(array $data, ?BlogPost $post = null, ?int $authorId = null): array
    {
        return [
            'blog_category_id' => $data['blog_category_id'] ?? null,
            'user_id' => $data['user_id'] ?? $post?->user_id ?? $authorId,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $post?->slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'published_at' => $data['published_at'] ?? $post?->published_at,
            'reading_minutes' => $this->estimateReadingMinutes($data['content']),
            'allow_comments' => (bool) ($data['allow_comments'] ?? false),
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];
    }

    /**
     * Comma-separated tag names are matched to existing tags by slug or
     * created on the fly, then synced to the post.
     */
    private function syncTags(BlogPost $post, string $tags): void
    {
        $names = collect(explode(',', $tags))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique(fn (string $name) => Str::slug($name));

        $ids = $names->map(function (string $name) {
            return BlogTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id;
        });

        $post->tags()->sync($ids->all());
    }

    /**
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removeIds
     */
    private function syncGallery(BlogPost $post, array $newImages, array $removeIds): void
    {
        foreach ($removeIds as $mediaId) {
            $post->getMedia('gallery')->firstWhere('id', $mediaId)?->delete();
        }

        foreach ($newImages as $image) {
            if ($image instanceof UploadedFile) {
                $post->addMedia($image)->toMediaCollection('gallery');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSeo(BlogPost $post, array $data): void
    {
        $seo = $post->seo()->firstOrNew();

        $seo->fill([
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_card' => $data['twitter_card'] ?? 'summary_large_image',
            'schema_type' => $data['schema_type'] ?? 'Article',
        ]);

        if ($data['og_image'] ?? null instanceof UploadedFile) {
            $media = $post->addMedia($data['og_image'])->toMediaCollection('og_image');
            $seo->og_image_media_id = $media->id;
        } elseif (! empty($data['remove_og_image'])) {
            $post->clearMediaCollection('og_image');
            $seo->og_image_media_id = null;
        }

        $post->seo()->save($seo);
    }
}
