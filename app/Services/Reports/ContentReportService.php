<?php

namespace App\Services\Reports;

use App\Enums\ActivityStatus;
use App\Enums\AlbumStatus;
use App\Enums\PostStatus;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\GalleryPhoto;
use App\Services\Reports\Concerns\BucketsByPeriod;
use App\Support\Reports\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Activity, blog and gallery aggregates (content reports). Nothing here
 * is personal data; the only "engagement" metric shown is the blog's
 * existing per-session view counter.
 */
class ContentReportService
{
    use BucketsByPeriod;

    /**
     * @param  array<string, mixed>  $filters  category (id), status
     */
    public function activities(DateRange $range, array $filters = []): array
    {
        $base = fn () => $this->activityBase($range, $filters);
        $statusCases = collect(ActivityStatus::cases())
            ->map(fn (ActivityStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as status_{$s->value}")
            ->implode(', ');

        $row = $base()->selectRaw("COUNT(*) as total, SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured, {$statusCases}")->first();

        $byStatus = [];
        $summary = ['total' => (int) $row->total, 'featured' => (int) $row->featured];
        foreach (ActivityStatus::cases() as $status) {
            $byStatus[$status->label()] = (int) $row->{'status_'.$status->value};
            $summary[$status->value] = (int) $row->{'status_'.$status->value};
        }

        return [
            'summary' => $summary,
            'byStatus' => $byStatus,
            'byCategory' => $this->groupedByRelation($base(), 'activity_category_id', ActivityCategory::query()->pluck('name', 'id'), 'Uncategorised'),
            'trend' => $this->series($base(), 'activity_date', $range),
            'recent' => $base()->with('category')->orderByDesc('activity_date')->limit(8)->get(['id', 'title', 'slug', 'status', 'is_featured', 'activity_date', 'activity_category_id']),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters  category (id)
     */
    public function blog(DateRange $range, array $filters = []): array
    {
        $base = fn () => $this->blogBase($range, $filters);
        $statusCases = collect(PostStatus::cases())
            ->map(fn (PostStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as status_{$s->value}")
            ->implode(', ');

        $row = $base()->selectRaw("COUNT(*) as total, SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured, COALESCE(SUM(views_count), 0) as views, {$statusCases}")->first();

        $byStatus = [];
        foreach (PostStatus::cases() as $status) {
            $byStatus[$status->label()] = (int) $row->{'status_'.$status->value};
        }

        return [
            'summary' => [
                'total' => (int) $row->total,
                'featured' => (int) $row->featured,
                'views' => (int) $row->views,
                'published' => (int) $row->{'status_'.PostStatus::Published->value},
                'draft' => (int) $row->{'status_'.PostStatus::Draft->value},
            ],
            'byStatus' => $byStatus,
            'byCategory' => $this->groupedByRelation($base(), 'blog_category_id', BlogCategory::query()->pluck('name', 'id'), 'Uncategorised'),
            'trend' => $this->series($base()->whereNotNull('published_at'), 'published_at', $range),
            'recent' => $base()->with('category')->orderByDesc('created_at')->limit(8)->get(['id', 'title', 'slug', 'status', 'is_featured', 'published_at', 'views_count', 'blog_category_id']),
            // Real per-session counter from the blog module — the only view data that exists.
            'mostViewed' => $base()->with('category')->where('views_count', '>', 0)->orderByDesc('views_count')->limit(8)->get(['id', 'title', 'slug', 'views_count', 'published_at', 'blog_category_id']),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters  category (id)
     */
    public function gallery(DateRange $range, array $filters = []): array
    {
        $base = fn () => $this->galleryBase($range, $filters);
        $statusCases = collect(AlbumStatus::cases())
            ->map(fn (AlbumStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as status_{$s->value}")
            ->implode(', ');

        $row = $base()->selectRaw("COUNT(*) as total, {$statusCases}")->first();
        $albumIds = $base()->pluck('id');

        $byStatus = [];
        foreach (AlbumStatus::cases() as $status) {
            $byStatus[$status->label()] = (int) $row->{'status_'.$status->value};
        }

        return [
            'summary' => [
                'albums' => (int) $row->total,
                'published' => (int) $row->{'status_'.AlbumStatus::Published->value},
                'photos' => $albumIds->isEmpty() ? 0 : GalleryPhoto::query()->whereIn('gallery_album_id', $albumIds)->count(),
                'activePhotos' => $albumIds->isEmpty() ? 0 : GalleryPhoto::query()->whereIn('gallery_album_id', $albumIds)->where('is_active', true)->count(),
            ],
            'byStatus' => $byStatus,
            'byCategory' => $this->groupedByRelation($base(), 'gallery_category_id', GalleryCategory::query()->pluck('name', 'id'), 'Uncategorised'),
            'recent' => $base()->with('category')->withCount('photos')->orderByDesc('created_at')->limit(8)->get(['id', 'title', 'slug', 'status', 'created_at', 'gallery_category_id']),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<list<string|int>>
     */
    public function exportRows(string $report, DateRange $range, array $filters = []): iterable
    {
        $data = match ($report) {
            'activities' => $this->activities($range, $filters),
            'blog' => $this->blog($range, $filters),
            'gallery' => $this->gallery($range, $filters),
        };

        foreach ($data['summary'] as $metric => $value) {
            yield ['Summary', ucfirst(str_replace('_', ' ', $metric)), $value];
        }

        foreach ($data['byStatus'] as $status => $count) {
            yield ['Status', $status, $count];
        }

        foreach ($data['byCategory'] as $category => $count) {
            yield ['Category', $category, $count];
        }

        foreach ($data['trend'] ?? [] as $period => $count) {
            yield ['Period', $period, $count];
        }
    }

    /**
     * @param  Collection<int, string>  $names
     * @return array<string, int>
     */
    private function groupedByRelation(Builder $query, string $column, Collection $names, string $fallback): array
    {
        $rows = $query->selectRaw("{$column} as key_id, COUNT(*) as aggregate")->groupBy($column)->orderByDesc('aggregate')->get();

        $result = [];
        foreach ($rows as $row) {
            $label = $row->key_id ? ($names[$row->key_id] ?? 'Deleted category') : $fallback;
            $result[$label] = ($result[$label] ?? 0) + (int) $row->aggregate;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function activityBase(DateRange $range, array $filters): Builder
    {
        [$from, $to] = $range->bounds();

        return Activity::query()
            ->whereBetween('activity_date', [$range->from->toDateString(), $range->to->toDateString()])
            ->when(! empty($filters['category']), fn (Builder $q) => $q->where('activity_category_id', (int) $filters['category']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function blogBase(DateRange $range, array $filters): Builder
    {
        [$from, $to] = $range->bounds();

        return BlogPost::query()
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['category']), fn (Builder $q) => $q->where('blog_category_id', (int) $filters['category']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function galleryBase(DateRange $range, array $filters): Builder
    {
        [$from, $to] = $range->bounds();

        return GalleryAlbum::query()
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['category']), fn (Builder $q) => $q->where('gallery_category_id', (int) $filters['category']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']));
    }
}
