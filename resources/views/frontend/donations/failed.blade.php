@extends('layouts.app')

@section('title', 'Donation Not Completed — '.config('app.name'))
@section('meta_description', 'Your donation payment could not be completed. You can try again at any time.')

@section('content')
    <x-ui.section background="base">
        <div class="mx-auto max-w-xl">
            <x-ui.card>
                <div class="text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                        <x-ui.icon name="x-mark" class="h-8 w-8 text-error-600" />
                    </div>

                    <h1 class="mt-5 font-display text-2xl font-semibold text-text-900 dark:text-night-text">Payment Not Completed</h1>

                    <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">
                        Unfortunately, your donation of <strong>{{ format_inr((float) $donation->amount) }}</strong>
                        @if ($donation->campaign)
                            toward <strong>{{ $donation->campaign->name }}</strong>
                        @endif
                        could not be processed. No money has been deducted in most cases — if an amount was deducted,
                        it is normally refunded automatically by your bank within 5–7 working days.
                    </p>

                    <p class="mt-3 text-xs text-text-400 dark:text-night-text-muted">Reference: {{ $donation->reference }}</p>

                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <x-ui.button href="{{ route('donations.donate', $donation->campaign) }}" variant="accent">Try Again</x-ui.button>
                        <x-ui.button href="{{ route('donations.campaigns.index') }}" variant="ghost">Browse Campaigns</x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </x-ui.section>
@endsection
