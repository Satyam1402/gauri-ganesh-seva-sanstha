@extends('layouts.admin')

@section('title', 'Partner — '.$partner->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Partners & Sponsors', 'url' => route('admin.partners.index')],
        ['label' => $partner->name],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $partner->name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                <x-ui.badge variant="neutral">{{ $partner->type?->name ?? 'No type' }}</x-ui.badge>
                <x-ui.badge :variant="$partner->status->badgeVariant()">{{ $partner->status->label() }}</x-ui.badge>
                @if ($partner->is_featured)
                    <x-ui.badge variant="accent">Featured</x-ui.badge>
                @endif
                @if ($partner->trashed())
                    <x-ui.badge variant="error">Trashed</x-ui.badge>
                @endif
                @if ($partner->partnershipPeriod())
                    <span>{{ $partner->partnershipPeriod() }}</span>
                @endif
            </p>
        </div>

        <div class="flex gap-3">
            @unless ($partner->trashed())
                <x-ui.button href="{{ route('admin.partners.edit', $partner) }}" size="sm">Edit</x-ui.button>
            @endunless
            <x-ui.button href="{{ route('admin.partners.index') }}" variant="ghost" size="sm">← Back to list</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Public Card Preview</h2>
                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">How the partner renders on the website. Contact details and notes are never included.</p>
                <div class="mt-4 max-w-xs">
                    <x-partners.card :partner="$partner" />
                </div>
            </div>

            @if ($partner->full_description)
                <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Full Description</h2>
                    <div class="prose mt-4 text-sm text-text-600 dark:text-night-text-muted">
                        {!! $partner->fullDescriptionHtml() !!}
                    </div>
                </div>
            @endif

            @if ($cover = $partner->getFirstMedia('cover_image'))
                <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Cover Image</h2>
                    <img src="{{ $cover->hasGeneratedConversion('thumb') ? $cover->getUrl('thumb') : $cover->getUrl() }}" alt="Cover image for {{ $partner->name }}" class="mt-4 w-full rounded-md object-cover">
                </div>
            @endif

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Admin Notes <span class="text-xs font-normal text-text-400 dark:text-night-text-muted">(private)</span></h2>
                @if ($partner->admin_notes)
                    <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $partner->admin_notes }}</p>
                @else
                    <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">No internal notes.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Actions</h2>

                <div class="mt-4 space-y-3">
                    @if ($partner->trashed())
                        <form method="POST" action="{{ route('admin.partners.restore', $partner) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" class="w-full">Restore Partner</x-ui.button>
                        </form>
                    @else
                        @if ($partner->isActive())
                            <form method="POST" action="{{ route('admin.partners.deactivate', $partner) }}">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Deactivate</x-ui.button>
                            </form>
                        @elseif ($partner->status->value !== 'archived')
                            <form method="POST" action="{{ route('admin.partners.activate', $partner) }}">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" class="w-full">Activate</x-ui.button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.partners.feature', $partner) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">{{ $partner->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}</x-ui.button>
                        </form>

                        @if ($partner->status->value !== 'archived')
                            <form method="POST" action="{{ route('admin.partners.archive', $partner) }}" onsubmit="return confirm('Archive this partner? It will be removed from the website.');">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Archive</x-ui.button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.partners.destroy', $partner) }}" onsubmit="return confirm('Move this partner to trash?');">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" size="sm" variant="danger" class="w-full">Move to Trash</x-ui.button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Contact <span class="text-xs font-normal text-text-400 dark:text-night-text-muted">(private)</span></h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Website</dt>
                        <dd class="mt-0.5 break-all text-text-900 dark:text-night-text">
                            @if ($partner->website_url)
                                <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="text-primary-700 hover:underline dark:text-night-text">{{ $partner->website_url }}<span class="sr-only"> (opens in a new tab)</span></a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Email</dt>
                        <dd class="mt-0.5 break-all text-text-900 dark:text-night-text">{{ $partner->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Phone</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Address</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->address ?? '—' }}{{ $partner->locationLine() ? ($partner->address ? ', ' : '').$partner->locationLine() : '' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Details</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Partnership</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->started_on?->format('d M Y') ?? '—' }}{{ $partner->ended_on ? ' → '.$partner->ended_on->format('d M Y') : ($partner->started_on ? ' → ongoing' : '') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Logo Alt Text</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->logoAlt() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Display Order</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->display_order }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Added By</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Created</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->created_at->format('d M Y, g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Last Updated</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->updated_at->format('d M Y, g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
