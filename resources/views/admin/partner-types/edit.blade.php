@extends('layouts.admin')

@section('title', 'Edit '.$type->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Partners & Sponsors', 'url' => route('admin.partners.index')],
        ['label' => 'Types', 'url' => route('admin.partner-types.index')],
        ['label' => $type->name],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ route('admin.partner-types.update', $type) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @include('admin.partner-types._form', ['category' => $type])

            <div class="flex gap-3">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <x-ui.button href="{{ route('admin.partner-types.index') }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
