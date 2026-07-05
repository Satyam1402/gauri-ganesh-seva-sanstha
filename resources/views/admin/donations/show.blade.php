@extends('layouts.admin')

@section('title', 'Donation Details')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Donations', 'url' => route('admin.donations.index')],
        ['label' => 'Donation #'.$donation->id],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">
                {{ format_inr((float) $donation->amount) }} from {{ $donation->donor_name }}
            </h2>
            <p class="mt-1 text-sm text-text-400 dark:text-night-text-muted">
                {{ $donation->donated_at->format('d M Y, h:i A') }} &middot; Reference {{ $donation->reference }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if ($donation->payment_status->value === 'pending')
                <form method="POST" action="{{ route('admin.donations.complete', $donation) }}" onsubmit="return confirm('Mark this donation as completed and email the receipt?');">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit">Verify &amp; Complete</x-ui.button>
                </form>
                <form method="POST" action="{{ route('admin.donations.fail', $donation) }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="danger">Mark Failed</x-ui.button>
                </form>
            @endif
            <x-ui.button href="{{ route('admin.donations.edit', $donation) }}" variant="secondary">Edit</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Donor Information</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Name</dt>
                    <dd class="text-right font-medium text-text-900 dark:text-night-text">
                        {{ $donation->donor_name }}
                        @if ($donation->is_anonymous)
                            <x-ui.badge variant="warning">Anonymous on site</x-ui.badge>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Email</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->donor_email }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Phone</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->donor_phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Address</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->donor_address ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">PAN</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->pan_number ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Payment Information</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Amount</dt>
                    <dd class="text-right font-semibold text-primary-700 dark:text-night-text">{{ format_inr((float) $donation->amount) }} {{ $donation->currency !== 'INR' ? $donation->currency : '' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Campaign</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">
                        @if ($donation->campaign)
                            <a href="{{ route('admin.donation-campaigns.edit', $donation->campaign) }}" class="text-primary-700 hover:underline dark:text-night-text">{{ $donation->campaign->name }}</a>
                        @else
                            General Donation
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Method</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->payment_method->label() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Status</dt>
                    <dd class="text-right"><x-ui.badge :variant="$donation->payment_status->badgeVariant()">{{ $donation->payment_status->label() }}</x-ui.badge></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Transaction ID</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->transaction_id ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Receipt Number</dt>
                    <dd class="text-right font-medium text-text-900 dark:text-night-text">{{ $donation->receipt_number ?? 'Issued on completion' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-text-400 dark:text-night-text-muted">Donated At</dt>
                    <dd class="text-right text-text-900 dark:text-night-text">{{ $donation->donated_at->format('d M Y, h:i A') }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>

    @if ($donation->remarks || $donation->meta)
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            @if ($donation->remarks)
                <x-ui.card>
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Remarks</h3>
                    <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">{{ $donation->remarks }}</p>
                </x-ui.card>
            @endif

            @if ($donation->meta)
                <x-ui.card>
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Gateway Data</h3>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-surface-muted p-3 text-xs text-text-600 dark:bg-night-surface-alt dark:text-night-text-muted">{{ json_encode($donation->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </x-ui.card>
            @endif
        </div>
    @endif
@endsection
