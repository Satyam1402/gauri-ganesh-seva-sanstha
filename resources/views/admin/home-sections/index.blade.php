@extends('layouts.admin')

@section('title', 'Homepage Sections')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Homepage']]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-text-600 dark:text-night-text-muted">Drag rows to reorder. Order saves automatically.</p>
        <x-ui.button href="{{ route('admin.pages.seo.edit', 'home') }}" variant="secondary">Homepage SEO</x-ui.button>
    </div>

    <ul
        x-data="{
            saving: false,
            async saveOrder() {
                this.saving = true;
                const ids = Array.from($el.children).map(li => li.dataset.id);
                await fetch('{{ route('admin.home-sections.reorder') }}', {
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
        @foreach ($sections as $section)
            <li
                draggable="true"
                data-id="{{ $section->id }}"
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
                    <span class="font-medium text-text-900 dark:text-night-text">{{ $section->name }}</span>
                    <x-ui.badge :variant="$section->is_active ? 'success' : 'neutral'">
                        {{ $section->is_active ? 'Enabled' : 'Disabled' }}
                    </x-ui.badge>
                </div>

                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('admin.home-sections.toggle', $section) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">
                            {{ $section->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.home-sections.edit', $section) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                        Edit
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
@endsection
