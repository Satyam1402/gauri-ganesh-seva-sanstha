@php
    $fields = [
        'Registered Name' => $orgProfile?->legal_name,
        'Registration No.' => $orgProfile?->registration_no,
        'Registration Date' => optional($orgProfile?->registration_date)->format('d M Y'),
        'PAN No.' => $orgProfile?->pan_no,
        'Trust Deed No.' => $orgProfile?->trust_deed_no,
        '80G Registration' => $orgProfile?->section_80g_no,
        '12A Registration' => $orgProfile?->section_12a_no,
        'Established' => $orgProfile?->established_year,
    ];
    $documents = $orgProfile?->getMedia('legal_documents') ?? collect();
@endphp

<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->description" align="center" class="mx-auto" />

    @if (collect($fields)->filter()->isNotEmpty())
        <dl class="mx-auto mt-10 grid max-w-3xl grid-cols-1 gap-x-8 gap-y-4 rounded-lg border border-border-subtle bg-surface-white p-6 sm:grid-cols-2 dark:border-night-border dark:bg-night-surface">
            @foreach ($fields as $label => $value)
                @if ($value)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $label }}</dt>
                        <dd class="mt-1 text-sm font-medium text-text-900 dark:text-night-text">{{ $value }}</dd>
                    </div>
                @endif
            @endforeach
        </dl>
    @else
        <x-ui.empty-state heading="Registration details coming soon" class="mx-auto mt-10 max-w-md" />
    @endif

    @if ($documents->isNotEmpty())
        <div class="mx-auto mt-6 max-w-3xl">
            <p class="mb-2 text-sm font-semibold text-text-900 dark:text-night-text">Documents</p>
            <ul class="flex flex-wrap gap-3">
                @foreach ($documents as $document)
                    <li>
                        <a href="{{ $document->getUrl() }}" target="_blank" class="inline-flex items-center gap-2 rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm text-primary-700 hover:underline dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h1m5 12H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V18a2 2 0 0 1-2 2Z" />
                            </svg>
                            {{ $document->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-ui.section>
