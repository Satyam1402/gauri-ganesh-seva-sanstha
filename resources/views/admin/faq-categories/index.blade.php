@extends('layouts.admin')

@section('title', 'FAQ Categories')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['label' => 'Categories'],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-text-600 dark:text-night-text-muted">Drag rows to reorder. Order saves automatically. Archived categories hide their FAQs from the website.</p>
        <div class="flex flex-wrap gap-3">
            <x-ui.button href="{{ route('admin.faqs.index') }}" variant="secondary">Back to FAQs</x-ui.button>
            @can('create', App\Models\FaqCategory::class)
                <x-ui.button href="{{ route('admin.faq-categories.create') }}">Add Category</x-ui.button>
            @endcan
        </div>
    </div>

    @if ($categories->isEmpty())
        <x-ui.empty-state heading="No categories yet" message="Create your first FAQ category — e.g. Donations, Volunteers, Payment." />
    @else
        <ul
            x-data="{
                saving: false,
                async saveOrder() {
                    this.saving = true;
                    const ids = Array.from($el.children).map(li => li.dataset.id);
                    await fetch('{{ route('admin.faq-categories.reorder') }}', {
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
                    <div class="flex flex-wrap items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5 text-text-400 dark:text-night-text-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                        </svg>
                        <div>
                            <span class="font-medium text-text-900 dark:text-night-text">{{ $category->name }}</span>
                            <span class="ml-2 text-xs text-text-400 dark:text-night-text-muted">/{{ $category->slug }}</span>
                        </div>
                        <x-ui.badge variant="neutral">{{ $category->faqs_count }} {{ Str::plural('FAQ', $category->faqs_count) }}</x-ui.badge>
                        <x-ui.badge :variant="$category->is_active ? 'success' : 'neutral'">
                            {{ $category->is_active ? 'Published' : 'Archived' }}
                        </x-ui.badge>
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('admin.faq-categories.toggle', $category) }}" @if ($category->is_active) onsubmit="return confirm('Archive {{ addslashes($category->name) }}? Its {{ $category->faqs_count }} {{ Str::plural('FAQ', $category->faqs_count) }} will be hidden from the website.');" @endif>
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">
                                {{ $category->is_active ? 'Archive' : 'Publish' }}
                            </button>
                        </form>

                        <a href="{{ route('admin.faq-categories.edit', $category) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                            Edit
                        </a>

                        @if ($category->faqs_count === 0)
                            <form method="POST" action="{{ route('admin.faq-categories.destroy', $category) }}" onsubmit="return confirm('Delete {{ addslashes($category->name) }}?');">
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
