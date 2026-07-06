@extends('layouts.admin')

@section('title', 'Application: '.$application->fullName())

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Volunteer Applications', 'url' => route('admin.volunteer-applications.index')],
        ['label' => $application->fullName()],
    ]" />
@endsection

@section('content')
    @php
        $detail = fn (string $label, ?string $value) => '
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">'.e($label).'</dt>
                <dd class="mt-0.5 text-sm text-text-900 dark:text-night-text">'.(trim((string) $value) !== '' ? nl2br(e($value)) : '—').'</dd>
            </div>';
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                @if ($photo = $application->getFirstMedia('profile_photo'))
                    <img src="{{ $photo->getUrl('thumb') }}" alt="{{ $application->fullName() }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-lg font-semibold text-text-400 dark:text-night-text-muted">
                        {{ strtoupper(substr($application->first_name, 0, 1).substr($application->last_name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div>
                <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $application->fullName() }}</h1>
                <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">
                    Applied {{ $application->created_at->format('d M Y, g:i A') }}
                    <x-ui.badge :variant="$application->status->badgeVariant()" class="ml-2">{{ $application->status->label() }}</x-ui.badge>
                    @if ($application->trashed())
                        <x-ui.badge variant="error" class="ml-1">Trashed</x-ui.badge>
                    @endif
                </p>
            </div>
        </div>

        <x-ui.button href="{{ route('admin.volunteer-applications.index') }}" variant="ghost">← Back to list</x-ui.button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: application details --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Personal Details</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {!! $detail('Gender', $application->gender->label()) !!}
                    {!! $detail('Date of Birth', $application->date_of_birth->format('d M Y').($application->age() !== null ? ' ('.$application->age().' years)' : '')) !!}
                    {!! $detail('Occupation', $application->occupation) !!}
                    {!! $detail('Organization', $application->organization) !!}
                </dl>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Contact & Address</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {!! $detail('Email', $application->email) !!}
                    {!! $detail('Preferred Communication', $application->preferred_communication_method->label()) !!}
                    {!! $detail('Phone', $application->phone) !!}
                    {!! $detail('Alternate Phone', $application->alternate_phone) !!}
                    {!! $detail('Address', $application->address) !!}
                    {!! $detail('City / State', $application->city.', '.$application->state) !!}
                    {!! $detail('Country', $application->country) !!}
                    {!! $detail('PIN Code', $application->pin_code) !!}
                </dl>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Volunteering Profile</h2>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Areas of Interest</dt>
                        <dd class="mt-1.5">
                            @foreach ($application->interestLabels() as $interest)
                                <x-ui.badge variant="accent" class="mb-1 mr-1">{{ $interest }}</x-ui.badge>
                            @endforeach
                        </dd>
                    </div>
                    {!! $detail('Availability', $application->availability->label()) !!}
                    {!! $detail('Skills', $application->skills) !!}
                    {!! $detail('Previous Experience', $application->experience) !!}
                    {!! $detail('Message', $application->message) !!}
                </dl>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Emergency & Wellbeing</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {!! $detail('Emergency Contact', $application->emergency_contact_name) !!}
                    {!! $detail('Emergency Phone', $application->emergency_contact_phone) !!}
                    <div class="sm:col-span-2">
                        {!! $detail('Medical Information', $application->medical_information) !!}
                    </div>
                </dl>
            </div>
        </div>

        {{-- Right: review workflow --}}
        <div class="space-y-6">
            @unless ($application->trashed())
                <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Review</h2>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('admin.volunteer-applications.approve', $application) }}" onsubmit="return confirm('Approve this application? The volunteer will be emailed.');">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" class="w-full">Approve</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.volunteer-applications.reject', $application) }}" onsubmit="return confirm('Reject this application? The applicant will be emailed.');">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" variant="danger" class="w-full">Reject</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.volunteer-applications.hold', $application) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Put On Hold</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.volunteer-applications.archive', $application) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Archive</x-ui.button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.volunteer-applications.update', $application) }}" class="mt-6 space-y-4 border-t border-border-subtle pt-5 dark:border-night-border">
                        @csrf
                        @method('PUT')

                        <x-ui.select label="Status" name="status" :options="$statuses" :selected="$application->status->value" :error="$errors->first('status')" />

                        <div>
                            <label for="admin_notes" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Internal Notes</label>
                            <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Visible to the team only — never shown to the applicant."
                                class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                            @error('admin_notes')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                        </div>

                        <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Save Status & Notes</x-ui.button>
                    </form>
                </div>
            @else
                <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Trashed Application</h2>
                    <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">Restore this application to resume the review workflow.</p>
                    <form method="POST" action="{{ route('admin.volunteer-applications.restore', $application) }}" class="mt-4">
                        @csrf @method('PATCH')
                        <x-ui.button type="submit" size="sm" class="w-full">Restore Application</x-ui.button>
                    </form>
                </div>
            @endunless

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Documents</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-text-600 dark:text-night-text-muted">Identity Proof</span>
                        @if ($application->getFirstMedia('identity_proof'))
                            <a href="{{ route('admin.volunteer-applications.document', [$application, 'identity_proof']) }}" class="font-medium text-primary-700 hover:underline dark:text-night-text">Download</a>
                        @else
                            <span class="text-text-400 dark:text-night-text-muted">Not provided</span>
                        @endif
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-text-600 dark:text-night-text-muted">Resume / CV</span>
                        @if ($application->getFirstMedia('resume'))
                            <a href="{{ route('admin.volunteer-applications.document', [$application, 'resume']) }}" class="font-medium text-primary-700 hover:underline dark:text-night-text">Download</a>
                        @else
                            <span class="text-text-400 dark:text-night-text-muted">Not provided</span>
                        @endif
                    </li>
                </ul>
                <p class="mt-4 text-xs text-text-400 dark:text-night-text-muted">Documents are stored on a private disk and only downloadable by authorized staff.</p>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Application Meta</h2>
                <dl class="mt-4 space-y-4">
                    {!! $detail('Reference', $application->reference) !!}
                    {!! $detail('Consent Given', $application->consented_at->format('d M Y, g:i A')) !!}
                    {!! $detail('Last Reviewed By', $application->reviewer?->name) !!}
                    {!! $detail('Last Reviewed At', $application->reviewed_at?->format('d M Y, g:i A')) !!}
                </dl>
            </div>
        </div>
    </div>
@endsection
