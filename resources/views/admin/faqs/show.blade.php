@extends('layouts.admin')

@section('title', 'FAQ — '.Str::limit($faq->question, 60))

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['label' => Str::limit($faq->question, 50)],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $faq->question }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                <x-ui.badge variant="neutral">{{ $faq->category?->name ?? 'Uncategorised' }}</x-ui.badge>
                <x-ui.badge :variant="$faq->status->badgeVariant()">{{ $faq->status->label() }}</x-ui.badge>
                @if ($faq->is_featured)
                    <x-ui.badge variant="accent">Featured</x-ui.badge>
                @endif
                @if ($faq->trashed())
                    <x-ui.badge variant="error">Trashed</x-ui.badge>
                @endif
            </p>
        </div>

        <div class="flex gap-3">
            @unless ($faq->trashed())
                <x-ui.button href="{{ route('admin.faqs.edit', $faq) }}" size="sm">Edit</x-ui.button>
            @endunless
            <x-ui.button href="{{ route('admin.faqs.index') }}" variant="ghost" size="sm">← Back to list</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Answer</h2>
                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Rendered exactly as it appears on the website.</p>
                <div class="prose mt-4 text-sm text-text-600 dark:text-night-text-muted">
                    {!! $faq->answerHtml() !!}
                </div>
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Admin Notes <span class="text-xs font-normal text-text-400 dark:text-night-text-muted">(private)</span></h2>
                @if ($faq->admin_notes)
                    <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $faq->admin_notes }}</p>
                @else
                    <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">No internal notes.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Actions</h2>

                <div class="mt-4 space-y-3">
                    @if ($faq->trashed())
                        <form method="POST" action="{{ route('admin.faqs.restore', $faq) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" class="w-full">Restore FAQ</x-ui.button>
                        </form>
                    @else
                        @if ($faq->isPublished())
                            <form method="POST" action="{{ route('admin.faqs.unpublish', $faq) }}">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Unpublish</x-ui.button>
                            </form>
                        @elseif ($faq->status->value !== 'archived')
                            <form method="POST" action="{{ route('admin.faqs.publish', $faq) }}">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" class="w-full">Publish</x-ui.button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.faqs.feature', $faq) }}">
                            @csrf @method('PATCH')
                            <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">{{ $faq->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}</x-ui.button>
                        </form>

                        @if ($faq->status->value !== 'archived')
                            <form method="POST" action="{{ route('admin.faqs.archive', $faq) }}" onsubmit="return confirm('Archive this FAQ? It will be removed from the website.');">
                                @csrf @method('PATCH')
                                <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Archive</x-ui.button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Move this FAQ to trash?');">
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
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Display Order</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $faq->display_order }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Published At</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">
                            {{ $faq->published_at?->format('d M Y, g:i A') ?? '—' }}
                            @if ($faq->isScheduled())
                                <span class="text-xs text-warning-600">(scheduled)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Deep Link</dt>
                        <dd class="mt-0.5 break-all text-text-900 dark:text-night-text">{{ route('faq.index') }}#{{ $faq->anchor() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Added By</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $faq->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Created</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $faq->created_at->format('d M Y, g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Last Updated</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $faq->updated_at->format('d M Y, g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
