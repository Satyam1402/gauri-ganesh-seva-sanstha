{{-- Topbar notification bell + dropdown (data resolved by App\View\Components\Admin\NotificationBell). --}}
<div class="relative" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="relative rounded-md p-2 text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt"
        :aria-expanded="open"
        aria-controls="notification-dropdown"
        aria-label="Notifications{{ $unreadCount > 0 ? ', '.$unreadCount.' unread' : '' }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.85 23.85 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-error-600 px-1 text-[11px] font-semibold leading-none text-white" data-unread-count>
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        id="notification-dropdown"
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-40 mt-2 w-80 rounded-lg border border-border-subtle bg-surface-white text-sm shadow-lg sm:w-96 dark:border-night-border dark:bg-night-surface"
    >
        <div class="flex items-center justify-between border-b border-border-subtle px-4 py-3 dark:border-night-border">
            <p class="font-semibold text-text-900 dark:text-night-text">Notifications</p>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-primary-700 hover:underline dark:text-night-text">Mark all as read</button>
                </form>
            @endif
        </div>

        @if ($recent->isEmpty())
            <p class="px-4 py-6 text-center text-text-400 dark:text-night-text-muted">You're all caught up — no notifications yet.</p>
        @else
            <ul class="max-h-96 divide-y divide-border-subtle overflow-y-auto dark:divide-night-border">
                @foreach ($recent as $notification)
                    @include('admin.notifications._item', ['notification' => $notification, 'compact' => true])
                @endforeach
            </ul>
        @endif

        <div class="border-t border-border-subtle px-4 py-2.5 text-center dark:border-night-border">
            <a href="{{ route('admin.notifications.index') }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-night-text">View all notifications</a>
        </div>
    </div>
</div>
