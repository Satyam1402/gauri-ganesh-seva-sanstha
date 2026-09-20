@extends('layouts.admin')

@section('title', 'Partnership Types')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Partners & Sponsors', 'url' => route('admin.partners.index')],
        ['label' => 'Types'],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-text-600 dark:text-night-text-muted">Drag rows to reorder. Order saves automatically. Hidden types hide their partners from the website.</p>
        <div class="flex flex-wrap gap-3">
            <x-ui.button href="{{ route('admin.partners.index') }}" variant="secondary">Back to Partners</x-ui.button>
            @can('create', App\Models\PartnerType::class)
                <x-ui.button href="{{ route('admin.partner-types.create') }}">Add Type</x-ui.button>
            @endcan
        </div>
    </div>

    @if ($types->isEmpty())
        <x-ui.empty-state heading="No types yet" message="Create your first partnership type — e.g. Sponsor, Corporate Partner, NGO Partner." />
    @else
        <ul
            x-data="{
                saving: false,
                async saveOrder() {
                    this.saving = true;
                    const ids = Array.from($el.children).map(li => li.dataset.id);
                    await fetch('{{ route('admin.partner-types.reorder') }}', {
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
            @foreach ($types as $type)
                <li
                    draggable="true"
                    data-id="{{ $type->id }}"
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
                            <span class="font-medium text-text-900 dark:text-night-text">{{ $type->name }}</span>
                            <span class="ml-2 text-xs text-text-400 dark:text-night-text-muted">/{{ $type->slug }}</span>
                        </div>
                        <x-ui.badge variant="neutral">{{ $type->partners_count }} {{ Str::plural('partner', $type->partners_count) }}</x-ui.badge>
                        <x-ui.badge :variant="$type->is_active ? 'success' : 'neutral'">
                            {{ $type->is_active ? 'Visible' : 'Hidden' }}
                        </x-ui.badge>
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('admin.partner-types.toggle', $type) }}" @if ($type->is_active) onsubmit="return confirm('Hide {{ addslashes($type->name) }}? Its {{ $type->partners_count }} {{ Str::plural('partner', $type->partners_count) }} will be hidden from the website.');" @endif>
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">
                                {{ $type->is_active ? 'Hide' : 'Show' }}
                            </button>
                        </form>

                        <a href="{{ route('admin.partner-types.edit', $type) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                            Edit
                        </a>

                        @if ($type->partners_count === 0)
                            <form method="POST" action="{{ route('admin.partner-types.destroy', $type) }}" onsubmit="return confirm('Delete {{ addslashes($type->name) }}?');">
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
