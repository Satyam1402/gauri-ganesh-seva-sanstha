@extends('layouts.app')

@section('content')
    @forelse ($sections as $section)
        @include('frontend.home.sections.'.str_replace('_', '-', $section->key->value), ['section' => $section])
    @empty
        <x-ui.container class="py-24">
            <x-ui.empty-state
                heading="Homepage content is being set up"
                message="Sections will appear here once enabled from the admin panel."
            />
        </x-ui.container>
    @endforelse
@endsection
