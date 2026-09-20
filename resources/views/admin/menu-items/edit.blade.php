@extends('layouts.admin')

@section('title', 'Edit Menu Item')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Navigation Menu', 'url' => route('admin.menu-items.index', ['location' => $location->value])],
        ['label' => $item->label],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-xl">
        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $location->label() }}</h2>
        <form method="POST" action="{{ route('admin.menu-items.update', $item) }}" class="mt-4 space-y-5">
            @csrf
            @method('PUT')
            @include('admin.menu-items._form', ['item' => $item])
            <div class="flex gap-3">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <x-ui.button href="{{ route('admin.menu-items.index', ['location' => $location->value]) }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
