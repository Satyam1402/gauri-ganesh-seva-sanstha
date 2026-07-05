@extends('layouts.admin')

@section('title', 'Add Post')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Blog', 'url' => route('admin.blog-posts.index')],
        ['label' => 'Add Post'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.blog-posts.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.blog-posts._form', ['post' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Post</x-ui.button>
            <x-ui.button href="{{ route('admin.blog-posts.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
