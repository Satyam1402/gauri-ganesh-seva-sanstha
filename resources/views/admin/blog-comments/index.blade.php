@extends('layouts.admin')

@section('title', 'Blog Comments')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Blog Posts', 'url' => route('admin.blog-posts.index')],
        ['label' => 'Comments'],
    ]" />
@endsection

@section('content')
    {{-- Status tabs with counts --}}
    <div class="mb-6 flex gap-1 border-b border-border-subtle dark:border-night-border">
        <a
            href="{{ route('admin.blog-comments.index', array_filter(['q' => $filters['q'] ?? null, 'post' => $filters['post'] ?? null])) }}"
            class="border-b-2 px-4 py-2.5 text-sm font-medium {{ empty($filters['status']) ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted' }}"
        >
            All ({{ array_sum($statusCounts) }})
        </a>
        @foreach ($statuses as $value => $label)
            <a
                href="{{ route('admin.blog-comments.index', array_filter(['status' => $value, 'q' => $filters['q'] ?? null, 'post' => $filters['post'] ?? null])) }}"
                class="border-b-2 px-4 py-2.5 text-sm font-medium {{ ($filters['status'] ?? null) === $value ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted' }}"
            >
                {{ $label }} ({{ $statusCounts[$value] ?? 0 }})
            </a>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.blog-comments.index') }}" class="flex flex-wrap items-end gap-3">
            @if (! empty($filters['status']))
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif

            <div class="w-56">
                <x-ui.input name="q" placeholder="Search name, email, comment..." value="{{ $filters['q'] ?? '' }}" />
            </div>

            <div class="w-64">
                <x-ui.select
                    name="post"
                    :options="['' => 'All Posts'] + $posts->all()"
                    :selected="$filters['post'] ?? ''"
                />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>

        <x-ui.button href="{{ route('admin.blog-posts.index') }}" variant="secondary">Back to Posts</x-ui.button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                <tr>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Comment</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Post</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Submitted</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                @forelse ($comments as $comment)
                    <tr>
                        <td class="max-w-md px-4 py-3">
                            <p class="font-medium text-text-900 dark:text-night-text">{{ $comment->name }}</p>
                            <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $comment->email }}@if ($comment->ip_address) · {{ $comment->ip_address }}@endif</p>
                            <p class="mt-1.5 text-text-600 dark:text-night-text-muted">{{ $comment->body }}</p>
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                            @if ($comment->post)
                                <a href="{{ route('blog.show', $comment->post->slug) }}" target="_blank" rel="noopener" class="text-primary-700 hover:underline dark:text-night-text">
                                    {{ Str::limit($comment->post->title, 40) }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $comment->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$comment->status->badgeVariant()">{{ $comment->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if ($comment->status->value !== 'approved')
                                    <form method="POST" action="{{ route('admin.blog-comments.update', $comment) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="text-sm text-success-600 hover:underline">Approve</button>
                                    </form>
                                @endif

                                @if ($comment->status->value !== 'rejected')
                                    <form method="POST" action="{{ route('admin.blog-comments.update', $comment) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="text-sm text-warning-600 hover:underline">Reject</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.blog-comments.destroy', $comment) }}" onsubmit="return confirm('Delete this comment permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-error-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                            No comments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $comments->links() }}
    </div>
@endsection
