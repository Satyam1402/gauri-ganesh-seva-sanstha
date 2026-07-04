@php $bg = $section->getFirstMedia('background_image'); @endphp

<section class="relative overflow-hidden bg-primary-900 text-text-inverse">
    @if ($bg)
        <img src="{{ $bg->getUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50" loading="eager">
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-primary-900/95 via-primary-900/60 to-transparent"></div>

    <x-ui.container class="relative py-16 lg:py-20">
        <nav aria-label="Breadcrumb" class="text-sm text-white/70">
            <a href="{{ route('home') }}" class="hover:text-white">Home</a>
            <span class="mx-2">/</span>
            <span class="text-white">About Us</span>
        </nav>

        @if ($section->heading)
            <h1 class="mt-4 font-display text-4xl font-semibold">{{ $section->heading }}</h1>
        @endif

        @if ($section->subheading)
            <p class="mt-3 max-w-2xl text-lg text-white/90">{{ $section->subheading }}</p>
        @endif
    </x-ui.container>
</section>
