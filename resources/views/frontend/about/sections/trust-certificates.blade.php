@php $certificates = $orgProfile?->getMedia('certificates') ?? collect(); @endphp

<x-ui.section background="muted">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->description" align="center" class="mx-auto" />

    @if ($certificates->isNotEmpty())
        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($certificates as $certificate)
                <a href="{{ $certificate->getUrl() }}" target="_blank" class="block overflow-hidden rounded-lg border border-border-subtle bg-surface-white dark:border-night-border">
                    @if (str_starts_with($certificate->mime_type, 'image/'))
                        <img
                            src="{{ $certificate->hasGeneratedConversion('webp') ? $certificate->getUrl('webp') : $certificate->getUrl() }}"
                            alt="{{ $certificate->name }}"
                            loading="lazy"
                            class="aspect-[4/3] w-full object-cover"
                        >
                    @else
                        <div class="flex aspect-[4/3] items-center justify-center bg-surface-muted text-text-400 dark:bg-night-surface-alt dark:text-night-text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h1m5 12H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V18a2 2 0 0 1-2 2Z" />
                            </svg>
                        </div>
                    @endif
                    <p class="truncate px-3 py-2 text-xs text-text-600 dark:text-night-text-muted">{{ $certificate->name }}</p>
                </a>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Certificates coming soon" class="mt-10" />
    @endif
</x-ui.section>
