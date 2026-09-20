@extends('layouts.admin')

@section('title', 'Edit Testimonial — '.$testimonial->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Testimonials', 'url' => route('admin.testimonials.index')],
        ['label' => $testimonial->name],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge :variant="$testimonial->status->badgeVariant()">{{ $testimonial->status->label() }}</x-ui.badge>
            <x-ui.badge :variant="$testimonial->type->badgeVariant()">{{ $testimonial->type->label() }}</x-ui.badge>
            @if ($testimonial->consent_given)
                <span class="text-sm text-success-600">Consent recorded{{ $testimonial->consented_at ? ' on '.$testimonial->consented_at->format('d M Y') : '' }}</span>
            @else
                <span class="text-sm text-error-600">Consent not recorded</span>
            @endif
            @if ($testimonial->isPublished() && ! $testimonial->isScheduled())
                <a href="{{ route('testimonials.index') }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View on Site ↗</a>
            @endif
        </div>

        <x-ui.button href="{{ route('admin.testimonials.show', $testimonial) }}" variant="secondary" size="sm">View Details</x-ui.button>
    </div>

    <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.testimonials._form', ['testimonial' => $testimonial])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.testimonials.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
