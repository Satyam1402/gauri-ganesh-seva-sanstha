@extends('layouts.admin')

@section('title', 'Add FAQ Category')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['label' => 'Categories', 'url' => route('admin.faq-categories.index')],
        ['label' => 'Add'],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ route('admin.faq-categories.store') }}" class="space-y-5">
            @csrf

            @include('admin.faq-categories._form', ['category' => null])

            <div class="flex gap-3">
                <x-ui.button type="submit">Create Category</x-ui.button>
                <x-ui.button href="{{ route('admin.faq-categories.index') }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
