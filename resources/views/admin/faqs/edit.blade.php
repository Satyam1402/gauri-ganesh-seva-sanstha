@extends('layouts.admin')

@section('title', 'Edit FAQ')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['label' => Str::limit($faq->question, 50)],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge :variant="$faq->status->badgeVariant()">{{ $faq->status->label() }}</x-ui.badge>
            @if ($faq->category)
                <x-ui.badge variant="neutral">{{ $faq->category->name }}</x-ui.badge>
            @endif
            @if ($faq->is_featured)
                <x-ui.badge variant="accent">Featured</x-ui.badge>
            @endif
            @if ($faq->isPublished() && ! $faq->isScheduled())
                <a href="{{ route('faq.index', array_filter(['category' => $faq->category?->slug])) }}#{{ $faq->anchor() }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View on Site ↗</a>
            @endif
        </div>

        <x-ui.button href="{{ route('admin.faqs.show', $faq) }}" variant="secondary" size="sm">View Details</x-ui.button>
    </div>

    <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.faqs._form', ['faq' => $faq])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.faqs.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
