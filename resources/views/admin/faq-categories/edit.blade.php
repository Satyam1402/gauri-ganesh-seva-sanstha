@extends('layouts.admin')

@section('title', 'Edit '.$category->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['label' => 'Categories', 'url' => route('admin.faq-categories.index')],
        ['label' => $category->name],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ route('admin.faq-categories.update', $category) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @include('admin.faq-categories._form', ['category' => $category])

            <div class="flex gap-3">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <x-ui.button href="{{ route('admin.faq-categories.index') }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
