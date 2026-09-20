@extends('layouts.admin')

@section('title', 'Add Testimonial')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Testimonials', 'url' => route('admin.testimonials.index')],
        ['label' => 'Add Testimonial'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.testimonials.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.testimonials._form', ['testimonial' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Testimonial</x-ui.button>
            <x-ui.button href="{{ route('admin.testimonials.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
