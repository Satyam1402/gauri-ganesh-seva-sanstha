@extends('layouts.admin')

@section('title', 'Testimonial — '.$testimonial->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Testimonials', 'url' => route('admin.testimonials.index')],
        ['label' => $testimonial->name],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $testimonial->name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                @if ($testimonial->attributionLine())
                    <span>{{ $testimonial->attributionLine() }}</span>
                @endif
                <x-ui.badge :variant="$testimonial->type->badgeVariant()">{{ $testimonial->type->label() }}</x-ui.badge>
                <x-ui.badge :variant="$testimonial->status->badgeVariant()">{{ $testimonial->status->label() }}</x-ui.badge>
                @if ($testimonial->is_featured)
                    <x-ui.badge variant="accent">Featured</x-ui.badge>
                @endif
                @if ($testimonial->trashed())
                    <x-ui.badge variant="error">Trashed</x-ui.badge>
                @endif
            </p>
        </div>

        <div class="flex gap-3">
            @unless ($testimonial->trashed())
                <x-ui.button href="{{ route('admin.testimonials.edit', $testimonial) }}" size="sm">Edit</x-ui.button>
            @endunless
            <x-ui.button href="{{ route('admin.testimonials.index') }}" variant="ghost" size="sm">← Back to list</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: preview + content --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Public Preview</h2>
                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Exactly how the card renders on the website. Admin notes are never included.</p>
                <div class="mt-4 max-w-md">
                    <x-testimonials.card :testimonial="$testimonial" />
                </div>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Full Text</h2>
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $testimonial->content }}</p>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Admin Notes <span class="text-xs font-normal text-text-400 dark:text-night-text-muted">(private)</span></h2>
                @if ($testimonial->admin_notes)
                    <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $testimonial->admin_notes }}</p>
                @else
                    <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">No internal notes.</p>
                @endif
            </div>
        </div>

        {{-- Right: actions + details --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Actions</h2>

                <div class="mt-4 space-y-3">
                    @if ($testimonial->trashed())
                        <form method="POST" action="{{ route('admin.testimonials.restore', $testimonial) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" class="w-full">Restore Testimonial</x-ui.button>
                        </form>
                    @else
                        @if ($testimonial->isPublished())
                            <form method="POST" action="{{ route('admin.testimonials.unpublish', $testimonial) }}">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Unpublish</x-ui.button>
                            </form>
                        @elseif ($testimonial->status->value !== 'archived')
                            <form method="POST" action="{{ route('admin.testimonials.publish', $testimonial) }}">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" class="w-full">Publish</x-ui.button>
                            </form>
                            @unless ($testimonial->consent_given)
                                <p class="text-xs text-error-600">Consent must be recorded before this can be published.</p>
                            @endunless
                        @endif

                        <form method="POST" action="{{ route('admin.testimonials.feature', $testimonial) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">{{ $testimonial->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}</x-ui.button>
                        </form>

                        @if ($testimonial->status->value !== 'archived')
                            <form method="POST" action="{{ route('admin.testimonials.archive', $testimonial) }}" onsubmit="return confirm('Archive this testimonial? It will be removed from the website.');">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Archive</x-ui.button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" onsubmit="return confirm('Move this testimonial to trash?');">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" size="sm" variant="danger" class="w-full">Move to Trash</x-ui.button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Details</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Consent</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">
                            @if ($testimonial->consent_given)
                                <span class="text-success-600">Given</span>{{ $testimonial->consented_at ? ' on '.$testimonial->consented_at->format('d M Y') : '' }}
                            @else
                                <span class="text-error-600">Not recorded</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Rating</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $testimonial->rating ? $testimonial->rating.' / '.App\Models\Testimonial::MAX_RATING : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Related To</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $testimonial->relatedLabel() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Display Order</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $testimonial->display_order }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Published At</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">
                            {{ $testimonial->published_at?->format('d M Y, g:i A') ?? '—' }}
                            @if ($testimonial->isScheduled())
                                <span class="text-xs text-warning-600">(scheduled)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Added By</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $testimonial->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Created</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $testimonial->created_at->format('d M Y, g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Last Updated</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $testimonial->updated_at->format('d M Y, g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
