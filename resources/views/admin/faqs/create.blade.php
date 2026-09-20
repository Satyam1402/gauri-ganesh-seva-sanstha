@extends('layouts.admin')

@section('title', 'Add FAQ')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['label' => 'Add FAQ'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-6">
        @csrf

        @include('admin.faqs._form', ['faq' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create FAQ</x-ui.button>
            <x-ui.button href="{{ route('admin.faqs.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
