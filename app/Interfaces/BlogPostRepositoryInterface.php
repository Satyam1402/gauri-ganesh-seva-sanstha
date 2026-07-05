<?php

namespace App\Interfaces;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BlogPostRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Filtered, paginated listing of live posts for the public blog.
     * Supports q, category (slug), and tag (slug) filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function publishedPaginated(array $filters, int $perPage = 9): LengthAwarePaginator;

    /**
     * Most recently published posts, cached.
     */
    public function latest(int $limit = 3): Collection;

    /**
     * Featured live posts, cached.
     */
    public function featuredList(int $limit = 3): Collection;

    /**
     * Most viewed live posts, cached.
     */
    public function popular(int $limit = 5): Collection;

    /**
     * Other live posts in the same category as the given post.
     */
    public function related(BlogPost $post, int $limit = 3): Collection;

    /**
     * Record one more public view of the post.
     */
    public function incrementViews(BlogPost $post): void;

    /**
     * Soft-delete a batch of posts. Returns the number deleted.
     *
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Set the status for a batch of posts. Returns the number updated.
     *
     * @param  list<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int;
}
