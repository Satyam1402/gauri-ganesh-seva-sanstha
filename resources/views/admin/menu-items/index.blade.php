@extends('layouts.admin')

@section('title', 'Navigation Menu')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.edit', 'general')],
        ['label' => 'Navigation Menu'],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <nav aria-label="Menu location" class="flex flex-wrap gap-2">
            @foreach ($locations as $value => $label)
                <a
                    href="{{ route('admin.menu-items.index', ['location' => $value]) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium {{ $location->value === $value ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    @if ($location->value === $value) aria-current="page" @endif
                >{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex gap-3">
            <x-ui.button href="{{ route('admin.settings.edit', 'navigation') }}" variant="secondary">Header Buttons</x-ui.button>
            @can('create', App\Models\MenuItem::class)
                <x-ui.button href="{{ route('admin.menu-items.create', ['location' => $location->value]) }}">Add Menu Item</x-ui.button>
            @endcan
        </div>
    </div>

    <p class="mb-4 text-sm text-text-600 dark:text-night-text-muted">
        Drag top-level rows to reorder — order saves automatically.
        @if ($location->supportsChildren())
            Items with a parent appear in a dropdown under it.
        @endif
    </p>

    @if ($items->isEmpty())
        <x-ui.empty-state heading="No menu items yet" message="Add the first link for this location.">
            <x-slot:action>
                <x-ui.button href="{{ route('admin.menu-items.create', ['location' => $location->value]) }}" size="sm">Add Menu Item</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <ul
            x-data="{
                async saveOrder() {
                    const ids = Array.from($el.querySelectorAll(':scope > li')).map(li => li.dataset.id);
                    await fetch('{{ route('admin.menu-items.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ location: '{{ $location->value }}', order: ids }),
                    });
                },
            }"
            @dragend="saveOrder"
            class="divide-y divide-border-subtle overflow-hidden rounded-lg border border-border-subtle bg-surface-white dark:divide-night-border dark:border-night-border dark:bg-night-surface"
        >
            @foreach ($items as $item)
                <li
                    draggable="true"
                    data-id="{{ $item->id }}"
                    x-on:dragstart="window.__dragEl = $el"
                    x-on:dragover.prevent
                    x-on:drop="
                        if (window.__dragEl && window.__dragEl !== $el && window.__dragEl.parentNode === $el.parentNode) {
                            const rect = $el.getBoundingClientRect();
                            const before = (event.clientY - rect.top) < rect.height / 2;
                            $el.parentNode.insertBefore(window.__dragEl, before ? $el : $el.nextSibling);
                        }
                    "
                    class="cursor-move"
                >
                    @include('admin.menu-items._row', ['item' => $item, 'depth' => 0])

                    @if ($item->children->isNotEmpty())
                        <ul class="divide-y divide-border-subtle border-t border-border-subtle bg-surface-muted/60 dark:divide-night-border dark:border-night-border dark:bg-night-surface-alt/40">
                            @foreach ($item->children as $child)
                                <li>@include('admin.menu-items._row', ['item' => $child, 'depth' => 1])</li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
@endsection
