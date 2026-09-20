{{-- One testimonial card. $clamp limits the quote to a few lines for compact sections. --}}
@props(['testimonial', 'clamp' => false])

@php
    $photo = $testimonial->getFirstMedia('profile_photo');
    $attribution = $testimonial->attributionLine();
@endphp

<figure {{ $attributes->class(['flex h-full flex-col rounded-lg border border-border-subtle bg-surface-white p-6 shadow-sm dark:border-night-border dark:bg-night-surface']) }}>
    <div class="flex items-start justify-between gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" class="h-7 w-7 shrink-0 text-primary-700/30 dark:text-night-text-muted" fill="currentColor">
            <path d="M7.17 6A5.17 5.17 0 0 0 2 11.17V18h6.5v-6.5H5.25c0-1.3.9-2.25 2.1-2.25V6h-.18ZM17.17 6A5.17 5.17 0 0 0 12 11.17V18h6.5v-6.5h-3.25c0-1.3.9-2.25 2.1-2.25V6h-.18Z" />
        </svg>
        <x-testimonials.rating :rating="$testimonial->rating" />
    </div>

    <blockquote class="mt-3 flex-1 text-base leading-relaxed text-text-600 dark:text-night-text-muted {{ $clamp ? 'line-clamp-6' : 'whitespace-pre-line' }}">{{ $testimonial->content }}</blockquote>

    <figcaption class="mt-5 flex items-center gap-3 border-t border-border-subtle pt-4 dark:border-night-border">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-100 font-display text-lg font-semibold text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
            @if ($photo)
                <x-ui.lazy-image :media="$photo" :alt="'Photo of '.$testimonial->name" conversion="avatar" width="48" height="48" />
            @else
                <span aria-hidden="true">{{ Str::upper(Str::substr($testimonial->name, 0, 1)) }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <p class="truncate font-semibold text-text-900 dark:text-night-text">{{ $testimonial->name }}</p>
            @if ($attribution)
                <p class="truncate text-xs text-text-400 dark:text-night-text-muted">{{ $attribution }}</p>
            @endif
            <x-ui.badge :variant="$testimonial->type->badgeVariant()" class="mt-1">{{ $testimonial->type->label() }}</x-ui.badge>
        </div>
    </figcaption>
</figure>
