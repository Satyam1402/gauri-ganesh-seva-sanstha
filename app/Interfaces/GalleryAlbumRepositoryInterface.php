<?php

namespace App\Interfaces;

use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface GalleryAlbumRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtered, sorted, paginated listing for the admin CRUD screen.
     *
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Filtered, paginated listing of published albums for the public
     * gallery, cached.
     *
     * @param  array<string, mixed>  $filters
     */
    public function publishedPaginated(array $filters, int $perPage = 12): LengthAwarePaginator;

    /**
     * Most recent published albums by event date, cached.
     */
    public function latest(int $limit = 6): Collection;

    /**
     * Featured published albums, cached.
     */
    public function featuredList(int $limit = 3): Collection;

    /**
     * Other published albums in the same category as the given album.
     */
    public function related(GalleryAlbum $album, int $limit = 3): Collection;
}
