@php $bg = $section->getFirstMedia('background_image'); @endphp

<section class="relative overflow-hidden bg-primary-900 text-text-inverse">
    @if ($bg)
        <img
            src="{{ $bg->getUrl() }}"
            alt=""
            class="absolute inset-0 h-full w-full object-cover opacity-60"
            loading="eager"
        >
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-primary-900/95 via-primary-900/50 to-transparent"></div>

    <x-ui.container class="relative py-24 text-center lg:py-32">
        @if ($section->heading)
            <h1 class="font-display text-4xl font-semibold sm:text-5xl">{{ $section->heading }}</h1>
        @endif

        @if ($section->subheading)
            <p class="mx-auto mt-4 max-w-2xl text-lg text-white/90">{{ $section->subheading }}</p>
        @endif

        @if ($section->buttons->isNotEmpty())
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                @foreach ($section->buttons as $button)
                    <x-ui.button href="{{ $button->url }}" :variant="$button->variant" size="lg">{{ $button->label }}</x-ui.button>
                @endforeach
            </div>
        @endif
    </x-ui.container>
</section>
