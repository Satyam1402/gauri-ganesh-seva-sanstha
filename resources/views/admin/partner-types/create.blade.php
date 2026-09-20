@extends('layouts.admin')

@section('title', 'Add Partnership Type')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Partners & Sponsors', 'url' => route('admin.partners.index')],
        ['label' => 'Types', 'url' => route('admin.partner-types.index')],
        ['label' => 'Add'],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ route('admin.partner-types.store') }}" class="space-y-5">
            @csrf

            @include('admin.partner-types._form', ['category' => null])

            <div class="flex gap-3">
                <x-ui.button type="submit">Create Type</x-ui.button>
                <x-ui.button href="{{ route('admin.partner-types.index') }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
