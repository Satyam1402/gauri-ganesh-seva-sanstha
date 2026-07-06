@extends('layouts.admin')

@section('title', 'Enquiry: '.$enquiry->subject)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Contact Enquiries', 'url' => route('admin.contact-enquiries.index')],
        ['label' => Str::limit($enquiry->subject, 40)],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $enquiry->subject }}</h1>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">
                From <span class="font-medium text-text-900 dark:text-night-text">{{ $enquiry->name }}</span>
                &lt;{{ $enquiry->email }}&gt;{{ $enquiry->phone ? ' · '.$enquiry->phone : '' }}
                · {{ $enquiry->created_at->format('d M Y, g:i A') }}
                <x-ui.badge variant="neutral" class="ml-1">{{ $enquiry->category->label() }}</x-ui.badge>
                <x-ui.badge :variant="$enquiry->status->badgeVariant()" class="ml-1">{{ $enquiry->status->label() }}</x-ui.badge>
                @if ($enquiry->trashed())
                    <x-ui.badge variant="error" class="ml-1">Trashed</x-ui.badge>
                @endif
            </p>
        </div>

        <x-ui.button href="{{ route('admin.contact-enquiries.index') }}" variant="ghost">← Back to list</x-ui.button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: message + conversation --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Message</h2>
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $enquiry->message }}</p>

                @if ($attachment = $enquiry->getFirstMedia('attachment'))
                    <div class="mt-5 flex items-center justify-between gap-3 rounded-md border border-border-subtle bg-surface-muted px-4 py-3 text-sm dark:border-night-border dark:bg-night-surface-alt">
                        <span class="text-text-600 dark:text-night-text-muted">📎 {{ $attachment->file_name }} ({{ $attachment->humanReadableSize }})</span>
                        <a href="{{ route('admin.contact-enquiries.attachment', $enquiry) }}" class="font-medium text-primary-700 hover:underline dark:text-night-text">Download</a>
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">
                    Conversation
                    <span class="text-sm font-normal text-text-400 dark:text-night-text-muted">({{ $enquiry->replies->count() }} {{ Str::plural('reply', $enquiry->replies->count()) }})</span>
                </h2>

                @forelse ($enquiry->replies as $reply)
                    <div class="mt-4 rounded-md border-l-4 border-primary-700 bg-surface-muted px-4 py-3 dark:bg-night-surface-alt">
                        <p class="text-xs font-medium text-text-400 dark:text-night-text-muted">
                            {{ $reply->author?->name ?? 'Staff (removed)' }} · {{ $reply->created_at->format('d M Y, g:i A') }}
                        </p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $reply->message }}</p>
                    </div>
                @empty
                    <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">No replies yet — the enquirer has only received the automatic acknowledgement.</p>
                @endforelse

                @unless ($enquiry->trashed())
                    <form method="POST" action="{{ route('admin.contact-enquiries.reply', $enquiry) }}" class="mt-6 space-y-3 border-t border-border-subtle pt-5 dark:border-night-border">
                        @csrf
                        <label for="message" class="block text-sm font-medium text-text-900 dark:text-night-text">Reply to {{ $enquiry->name }}</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Your reply will be emailed to {{ $enquiry->email }}."
                            class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-sm text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('message') }}</textarea>
                        @error('message')<p class="text-xs text-error-600">{{ $message }}</p>@enderror
                        <x-ui.button type="submit">Send Reply</x-ui.button>
                    </form>
                @endunless
            </div>
        </div>

        {{-- Right: triage --}}
        <div class="space-y-6">
            @unless ($enquiry->trashed())
                <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Triage</h2>

                    <form method="POST" action="{{ route('admin.contact-enquiries.update', $enquiry) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <x-ui.select label="Status" name="status" :options="$statuses" :selected="$enquiry->status->value" :error="$errors->first('status')" />

                        <x-ui.select label="Assigned To" name="assigned_to" :options="['' => 'Unassigned'] + $staff->all()" :selected="$enquiry->assigned_to" :error="$errors->first('assigned_to')" helper="Future-ready: assign a staff member responsible for this enquiry." />

                        <div>
                            <label for="admin_notes" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Internal Notes</label>
                            <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Visible to the team only — never sent to the enquirer."
                                class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('admin_notes', $enquiry->admin_notes) }}</textarea>
                            @error('admin_notes')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                        </div>

                        <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">Save Triage</x-ui.button>
                    </form>
                </div>
            @else
                <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Trashed Enquiry</h2>
                    <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">Restore this enquiry to resume the conversation.</p>
                    <form method="POST" action="{{ route('admin.contact-enquiries.restore', $enquiry) }}" class="mt-4">
                        @csrf @method('PATCH')
                        <x-ui.button type="submit" size="sm" class="w-full">Restore Enquiry</x-ui.button>
                    </form>
                </div>
            @endunless

            <div class="rounded-lg border border-border-subtle bg-surface-white p-6 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Details</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Reference</dt>
                        <dd class="mt-0.5 break-all text-text-900 dark:text-night-text">{{ $enquiry->reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Consent Given</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $enquiry->consented_at->format('d M Y, g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Last Replied</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $enquiry->replied_at?->format('d M Y, g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Sender IP</dt>
                        <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $enquiry->ip_address ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
