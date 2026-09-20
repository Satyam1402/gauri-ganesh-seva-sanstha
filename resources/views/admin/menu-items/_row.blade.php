{{-- One menu item row in the admin list. $depth = 1 for dropdown children. --}}
<div class="flex flex-wrap items-center justify-between gap-4 px-4 py-3 {{ $depth ? 'pl-12' : '' }}">
    <div class="flex min-w-0 items-center gap-3">
        @if (! $depth)
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5 shrink-0 text-text-400 dark:text-night-text-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
            </svg>
        @else
            <span class="text-text-400 dark:text-night-text-muted" aria-hidden="true">↳</span>
        @endif
        <div class="min-w-0">
            <span class="font-medium text-text-900 dark:text-night-text">{{ $item->label }}</span>
            <span class="ml-2 truncate text-xs text-text-400 dark:text-night-text-muted">
                {{ $item->link_type->label() }} · {{ $item->link_type->value === 'route' ? (App\Models\MenuItem::LINKABLE_ROUTES[$item->route_name] ?? $item->route_name) : $item->url }}{{ $item->open_in_new_tab ? ' · new tab' : '' }}
            </span>
        </div>
        <x-ui.badge :variant="$item->is_active ? 'success' : 'neutral'">{{ $item->is_active ? 'Enabled' : 'Disabled' }}</x-ui.badge>
    </div>

    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('admin.menu-items.toggle', $item) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">{{ $item->is_active ? 'Disable' : 'Enable' }}</button>
        </form>
        <a href="{{ route('admin.menu-items.edit', $item) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>
        <form method="POST" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('Delete “{{ addslashes($item->label) }}”{{ $item->relationLoaded('children') && $item->children->isNotEmpty() ? ' and its dropdown items' : '' }}?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-error-600 hover:underline">Delete</button>
        </form>
    </div>
</div>
