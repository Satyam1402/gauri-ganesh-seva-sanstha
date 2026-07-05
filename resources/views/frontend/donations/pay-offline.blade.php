@extends('layouts.app')

@section('title', 'Complete Your Donation — '.config('app.name'))
@section('meta_description', 'Instructions to complete your donation to '.config('app.name').'.')

@section('content')
    <x-ui.section background="base">
        <div class="mx-auto max-w-2xl space-y-6">
            <x-ui.card>
                <div class="text-center">
                    <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">Almost There!</h1>
                    <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">
                        Your donation of <strong class="text-primary-700 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</strong>
                        @if ($donation->campaign)
                            toward <strong>{{ $donation->campaign->name }}</strong>
                        @endif
                        has been recorded and is awaiting payment.
                    </p>
                    <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Reference: {{ $donation->reference }}</p>
                </div>
            </x-ui.card>

            @if ($method === 'bank_transfer')
                <x-ui.card>
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Bank Transfer Details</h2>

                    @if ($bank['account_number'])
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-text-400 dark:text-night-text-muted">Account Name</dt>
                                <dd class="text-right font-medium text-text-900 dark:text-night-text">{{ $bank['account_name'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-text-400 dark:text-night-text-muted">Account Number</dt>
                                <dd class="text-right font-medium text-text-900 dark:text-night-text">{{ $bank['account_number'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-text-400 dark:text-night-text-muted">IFSC Code</dt>
                                <dd class="text-right font-medium text-text-900 dark:text-night-text">{{ $bank['ifsc'] }}</dd>
                            </div>
                            @if ($bank['bank_name'])
                                <div class="flex justify-between gap-4">
                                    <dt class="text-text-400 dark:text-night-text-muted">Bank</dt>
                                    <dd class="text-right text-text-900 dark:text-night-text">{{ $bank['bank_name'] }}{{ $bank['branch'] ? ', '.$bank['branch'] : '' }}</dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="mt-4 text-sm text-text-600 dark:text-night-text-muted">
                            Please contact our office for bank transfer details, quoting your reference number above.
                        </p>
                    @endif
                </x-ui.card>
            @endif

            @if ($method === 'upi')
                <x-ui.card>
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Pay via UPI</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-400 dark:text-night-text-muted">UPI ID</dt>
                            <dd class="text-right font-medium text-text-900 dark:text-night-text">{{ $upi['vpa'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-400 dark:text-night-text-muted">Payee Name</dt>
                            <dd class="text-right text-text-900 dark:text-night-text">{{ $upi['payee_name'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 text-center">
                        <x-ui.button href="{{ $upi['link'] }}" variant="accent">Open UPI App</x-ui.button>
                        <p class="mt-2 text-xs text-text-400 dark:text-night-text-muted">On mobile, this opens Google Pay, PhonePe, or any UPI app with the amount prefilled.</p>
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card>
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">What Happens Next?</h2>
                <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-text-600 dark:text-night-text-muted">
                    <li>Complete the payment using the details above, quoting reference <strong class="text-text-900 dark:text-night-text">{{ $donation->reference }}</strong> in the transfer note if possible.</li>
                    <li>Our team verifies the payment — usually within 1–2 working days.</li>
                    <li>Your official donation receipt is emailed to <strong class="text-text-900 dark:text-night-text">{{ $donation->donor_email }}</strong>.</li>
                </ol>

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('donations.campaigns.index') }}" variant="secondary">Browse More Campaigns</x-ui.button>
                    <x-ui.button href="{{ route('home') }}" variant="ghost">Back to Home</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </x-ui.section>
@endsection
