{{--
    One in-app notification row. Used by the bell dropdown ($compact = true)
    and the notification centre list. $notification is a DatabaseNotification;
    its data payload holds category / title / message / url only.
--}}
@php
    $data = $notification->data;
    $category = \App\Enums\NotificationCategory::tryFrom((string) ($data['category'] ?? ''));
    $unread = $notification->read_at === null;
    $compact = $compact ?? false;
@endphp

<li class="{{ $unread ? 'bg-primary-100/40 dark:bg-night-surface-alt/60' : '' }}">
    <div class="flex items-start gap-3 px-4 {{ $compact ? 'py-3' : 'py-4' }}">
        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $unread ? 'bg-primary-700' : 'bg-transparent' }}" aria-hidden="true"></span>

        <div class="min-w-0 flex-1">
            <a href="{{ route('admin.notifications.open', $notification->id) }}" class="block">
                <p class="{{ $unread ? 'font-semibold text-text-900 dark:text-night-text' : 'font-medium text-text-600 dark:text-night-text-muted' }} {{ $compact ? 'line-clamp-2' : '' }}">
                    {{ $data['title'] ?? 'Notification' }}
                </p>
                @if (! empty($data['message']))
                    <p class="mt-0.5 text-xs text-text-400 dark:text-night-text-muted {{ $compact ? 'line-clamp-1' : '' }}">{{ $data['message'] }}</p>
                @endif
            </a>

            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-text-400 dark:text-night-text-muted">
                @if ($category)
                    <x-ui.badge :variant="$category->badgeVariant()">{{ $category->label() }}</x-ui.badge>
                @endif
                <time datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('d M Y, g:i A') }}">
                    {{ $notification->created_at->diffForHumans() }}
                </time>
                @if (! $compact && $notification->read_at)
                    <span>Read {{ $notification->read_at->diffForHumans() }}</span>
                @endif
            </div>
        </div>

        @unless ($compact)
            <div class="flex shrink-0 items-center gap-1">
                @if ($unread)
                    <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md p-1.5 text-text-400 hover:bg-surface-muted hover:text-primary-700 dark:hover:bg-night-surface-alt" title="Mark as read" aria-label="Mark as read">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.notifications.destroy', $notification->id) }}" onsubmit="return confirm('Delete this notification?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md p-1.5 text-text-400 hover:bg-red-50 hover:text-error-600 dark:hover:bg-night-surface-alt" title="Delete" aria-label="Delete notification">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </form>
            </div>
        @endunless
    </div>
</li>
