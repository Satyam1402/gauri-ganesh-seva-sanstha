@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <x-reports.kpi label="Posts" :value="$s['total']" hint="Created in period" />
        <x-reports.kpi label="Published" :value="$s['published']" tone="success" />
        <x-reports.kpi label="Draft" :value="$s['draft']" />
        <x-reports.kpi label="Featured" :value="$s['featured']" tone="accent" />
        <x-reports.kpi label="Views" :value="$s['views']" hint="Per-session counter" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Posts Published</h2>
            <x-charts.bars :data="$data['trend']" title="Posts by publish period" class="mt-4" />
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Category</h2>
            <x-charts.donut :data="$data['byCategory']" title="Posts by category" class="mt-4" />
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Most Viewed</h2>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">From the blog's own per-session view counter — no external analytics.</p>
            <ol class="mt-4 divide-y divide-border-subtle text-sm dark:divide-night-border">
                @forelse ($data['mostViewed'] as $post)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <a href="{{ route('admin.blog-posts.edit', $post) }}" class="min-w-0 truncate font-medium text-text-900 hover:underline dark:text-night-text">{{ $post->title }}</a>
                        <span class="shrink-0 text-text-600 dark:text-night-text-muted">{{ number_format($post->views_count) }} views</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-text-400 dark:text-night-text-muted">No views recorded for posts in this period yet.</li>
                @endforelse
            </ol>
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Recent Posts</h2>
            <ul class="mt-4 divide-y divide-border-subtle text-sm dark:divide-night-border">
                @forelse ($data['recent'] as $post)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="min-w-0">
                            <a href="{{ route('admin.blog-posts.edit', $post) }}" class="font-medium text-text-900 hover:underline dark:text-night-text">{{ $post->title }}</a>
                            <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $post->published_at?->format('d M Y') ?? 'Not published' }}{{ $post->category ? ' · '.$post->category->name : '' }}</span>
                        </span>
                        <span class="flex items-center gap-2">
                            @if ($post->is_featured)<x-ui.badge variant="accent">Featured</x-ui.badge>@endif
                            <x-ui.badge :variant="$post->status->badgeVariant()">{{ $post->status->label() }}</x-ui.badge>
                        </span>
                    </li>
                @empty
                    <li class="py-6 text-center text-text-400 dark:text-night-text-muted">No posts created in this period.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection
