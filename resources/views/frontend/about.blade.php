@extends('layouts.app')

@section('content')
    @forelse ($sections as $section)
        {{-- Community voices sit just above the closing CTAs (Phase 12). --}}
        @if ($section->key === App\Enums\AboutSectionKey::DonateCta)
            <x-testimonials-section heading="What People Say About Us" subheading="Trust, told by the people who have experienced our work firsthand." :featured="true" :limit="3" background="white" />
            <x-faq-section heading="Questions About the Sanstha" category="organization" :limit="5" background="muted" />
            <x-partners-section heading="Organisations We Work With" subheading="Trusted partners and sponsors who stand with us in service." :featured="true" :limit="8" background="white" />
        @endif
        @include('frontend.about.sections.'.str_replace('_', '-', $section->key->value), ['section' => $section, 'orgProfile' => $orgProfile])
    @empty
        <x-ui.container class="py-24">
            <x-ui.empty-state
                heading="About page content is being set up"
                message="Sections will appear here once enabled from the admin panel."
            />
        </x-ui.container>
    @endforelse
@endsection
