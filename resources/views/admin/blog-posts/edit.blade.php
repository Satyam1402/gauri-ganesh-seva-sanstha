@extends('layouts.admin')

@section('title', 'Edit '.$post->title)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Blog', 'url' => route('admin.blog-posts.index')],
        ['label' => $post->title],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge :variant="$post->status->badgeVariant()">{{ $post->status->label() }}</x-ui.badge>
            @if ($post->isScheduled())
                <x-ui.badge variant="accent">Scheduled: {{ $post->published_at->format('d M Y, g:i A') }}</x-ui.badge>
            @endif
            <span class="text-sm text-text-600 dark:text-night-text-muted">
                {{ $post->reading_minutes }} min read · {{ number_format($post->views_count) }} {{ Str::plural('view', $post->views_count) }}
            </span>
            @if ($post->isLive())
                <a href="{{ route('blog.show', $post) }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View on Site ↗</a>
            @endif
        </div>

        <x-ui.button href="{{ route('admin.blog-comments.index', ['post' => $post->id]) }}" variant="secondary" size="sm">
            Comments ({{ $post->comments_count }})
        </x-ui.button>
    </div>

    <form method="POST" action="{{ route('admin.blog-posts.update', $post) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.blog-posts._form', ['post' => $post])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.blog-posts.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
