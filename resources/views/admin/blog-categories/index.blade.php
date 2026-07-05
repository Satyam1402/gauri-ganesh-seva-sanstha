@extends('layouts.admin')

@section('title', 'Blog Categories')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Blog', 'url' => route('admin.blog-posts.index')],
        ['label' => 'Categories'],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-text-600 dark:text-night-text-muted">Drag rows to reorder. Order saves automatically.</p>
        <div class="flex flex-wrap gap-3">
            <x-ui.button href="{{ route('admin.blog-posts.index') }}" variant="secondary">Back to Posts</x-ui.button>
            @can('create', App\Models\BlogCategory::class)
                <x-ui.button href="{{ route('admin.blog-categories.create') }}">Add Category</x-ui.button>
            @endcan
        </div>
    </div>

    @if ($categories->isEmpty())
        <x-ui.empty-state heading="No categories yet" message="Create your first blog category to get started." />
    @else
        <ul
            x-data="{
                saving: false,
                async saveOrder() {
                    this.saving = true;
                    const ids = Array.from($el.children).map(li => li.dataset.id);
                    await fetch('{{ route('admin.blog-categories.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ order: ids }),
                    });
                    this.saving = false;
                },
            }"
            @dragend="saveOrder"
            class="divide-y divide-border-subtle overflow-hidden rounded-lg border border-border-subtle bg-surface-white dark:divide-night-border dark:border-night-border dark:bg-night-surface"
        >
            @foreach ($categories as $category)
                <li
                    draggable="true"
                    data-id="{{ $category->id }}"
                    x-on:dragstart="window.__dragEl = $el"
                    x-on:dragover.prevent
                    x-on:drop="
                        if (window.__dragEl && window.__dragEl !== $el) {
                            const rect = $el.getBoundingClientRect();
                            const before = (event.clientY - rect.top) < rect.height / 2;
                            $el.parentNode.insertBefore(window.__dragEl, before ? $el : $el.nextSibling);
                        }
                    "
                    class="flex cursor-move flex-wrap items-center justify-between gap-4 px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-text-400 dark:text-night-text-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                        </svg>
                        <span class="font-medium text-text-900 dark:text-night-text">{{ $category->name }}</span>
                        <x-ui.badge variant="neutral">{{ $category->posts_count }} {{ Str::plural('post', $category->posts_count) }}</x-ui.badge>
                        <x-ui.badge :variant="$category->is_active ? 'success' : 'neutral'">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.blog-categories.edit', $category) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                            Edit
                        </a>

                        @if ($category->posts_count === 0)
                            <form method="POST" action="{{ route('admin.blog-categories.destroy', $category) }}" onsubmit="return confirm('Delete {{ $category->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-error-600 hover:underline">Delete</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
