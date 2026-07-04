@props(['items' => []])

{{-- $items: ordered array of ['label' => string, 'url' => string|null]; last item (no url) renders as current page. --}}
<nav aria-label="Breadcrumb" {{ $attributes->class(['text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-2 text-text-400 dark:text-night-text-muted">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                @endif

                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="text-primary-700 hover:underline dark:text-night-text">{{ $item['label'] }}</a>
                @else
                    <span class="text-text-600 dark:text-night-text-muted">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
