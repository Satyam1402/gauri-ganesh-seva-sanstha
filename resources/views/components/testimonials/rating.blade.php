{{-- Five-star rating, screen-reader friendly. Renders nothing without a rating. --}}
@props(['rating' => null, 'max' => \App\Models\Testimonial::MAX_RATING])

@if ($rating)
    <span role="img" aria-label="Rated {{ $rating }} out of {{ $max }}" {{ $attributes->class(['inline-flex items-center gap-0.5']) }}>
        @for ($i = 1; $i <= $max; $i++)
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true" class="h-4 w-4 {{ $i <= $rating ? 'text-accent-500' : 'text-border-subtle dark:text-night-border' }}" fill="currentColor">
                <path d="M10 1.5l2.47 5.36 5.86.63-4.36 3.98 1.2 5.78L10 14.3l-5.17 2.95 1.2-5.78L1.67 7.49l5.86-.63L10 1.5z" />
            </svg>
        @endfor
    </span>
@endif
