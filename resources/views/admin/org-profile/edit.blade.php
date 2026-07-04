@extends('layouts.admin')

@section('title', 'Registration & Certificates')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'About Us', 'url' => route('admin.about-sections.index')],
        ['label' => 'Registration & Certificates'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.org-profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card class="max-w-3xl">
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Legal & Registration Details</h3>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Powers the Registration & Legal Information section of the About page.</p>

            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.input label="Legal Name" name="legal_name" value="{{ old('legal_name', $profile->legal_name) }}" :error="$errors->first('legal_name')" />
                <x-ui.input label="Short Name" name="short_name" value="{{ old('short_name', $profile->short_name) }}" :error="$errors->first('short_name')" />
                <x-ui.input label="Registration No." name="registration_no" value="{{ old('registration_no', $profile->registration_no) }}" :error="$errors->first('registration_no')" />
                <x-ui.input label="Registration Date" name="registration_date" type="date" value="{{ old('registration_date', optional($profile->registration_date)->format('Y-m-d')) }}" :error="$errors->first('registration_date')" />
                <x-ui.input label="PAN No." name="pan_no" value="{{ old('pan_no', $profile->pan_no) }}" :error="$errors->first('pan_no')" />
                <x-ui.input label="Trust Deed No." name="trust_deed_no" value="{{ old('trust_deed_no', $profile->trust_deed_no) }}" :error="$errors->first('trust_deed_no')" />
                <x-ui.input label="80G Registration No." name="section_80g_no" value="{{ old('section_80g_no', $profile->section_80g_no) }}" :error="$errors->first('section_80g_no')" />
                <x-ui.input label="12A Registration No." name="section_12a_no" value="{{ old('section_12a_no', $profile->section_12a_no) }}" :error="$errors->first('section_12a_no')" />
                <x-ui.input label="Established Year" name="established_year" value="{{ old('established_year', $profile->established_year) }}" :error="$errors->first('established_year')" />
            </div>
        </x-ui.card>

        <x-ui.card class="max-w-3xl">
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Trust Certificates</h3>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Shown as a gallery on the Trust Certificates section (images or PDFs).</p>

            @if ($profile->getMedia('certificates')->isNotEmpty())
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($profile->getMedia('certificates') as $media)
                        <label class="group relative block overflow-hidden rounded-md border border-border-subtle dark:border-night-border">
                            <img src="{{ $media->hasGeneratedConversion('webp') ? $media->getUrl('webp') : $media->getUrl() }}" alt="{{ $media->name }}" class="h-24 w-full object-cover">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-has-[:checked]:opacity-100 group-hover:opacity-100">
                                <input type="checkbox" name="remove_certificate_ids[]" value="{{ $media->id }}" class="h-5 w-5 rounded text-error-600">
                            </div>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Check an image and save to remove it.</p>
            @endif

            <input type="file" name="new_certificates[]" accept="image/png,image/jpeg,image/webp,application/pdf" multiple class="mt-4 block w-full text-sm text-text-600 dark:text-night-text-muted">
            @error('new_certificates.*')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </x-ui.card>

        <x-ui.card class="max-w-3xl">
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Registration Documents</h3>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Downloadable documents linked from the Registration & Legal Information section.</p>

            @if ($profile->getMedia('legal_documents')->isNotEmpty())
                <ul class="mt-4 divide-y divide-border-subtle rounded-md border border-border-subtle dark:divide-night-border dark:border-night-border">
                    @foreach ($profile->getMedia('legal_documents') as $media)
                        <li class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
                            <a href="{{ $media->getUrl() }}" target="_blank" class="text-primary-700 hover:underline dark:text-night-text">{{ $media->name }}</a>
                            <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                                <input type="checkbox" name="remove_document_ids[]" value="{{ $media->id }}" class="rounded border-border-subtle text-error-600">
                                Remove
                            </label>
                        </li>
                    @endforeach
                </ul>
            @endif

            <input type="file" name="new_documents[]" accept="image/png,image/jpeg,image/webp,application/pdf" multiple class="mt-4 block w-full text-sm text-text-600 dark:text-night-text-muted">
            @error('new_documents.*')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </x-ui.card>

        <x-ui.button type="submit">Save Changes</x-ui.button>
    </form>
@endsection
