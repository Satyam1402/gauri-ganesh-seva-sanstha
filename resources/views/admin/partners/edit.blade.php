@extends('layouts.admin')

@section('title', 'Edit '.$partner->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Partners & Sponsors', 'url' => route('admin.partners.index')],
        ['label' => $partner->name],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge :variant="$partner->status->badgeVariant()">{{ $partner->status->label() }}</x-ui.badge>
            @if ($partner->type)
                <x-ui.badge variant="neutral">{{ $partner->type->name }}</x-ui.badge>
            @endif
            @if ($partner->is_featured)
                <x-ui.badge variant="accent">Featured</x-ui.badge>
            @endif
            @if ($partner->isActive())
                <a href="{{ route('partners.show', $partner) }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View on Site ↗</a>
            @endif
        </div>

        <x-ui.button href="{{ route('admin.partners.show', $partner) }}" variant="secondary" size="sm">View Details</x-ui.button>
    </div>

    <form method="POST" action="{{ route('admin.partners.update', $partner) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.partners._form', ['partner' => $partner])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.partners.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
