@props(['media' => null, 'alt' => '', 'conversion' => 'webp'])

@if ($media)
    <img
        src="{{ $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl() }}"
        alt="{{ $alt }}"
        loading="lazy"
        decoding="async"
        {{ $attributes->class(['h-full w-full object-cover']) }}
    />
@endif
