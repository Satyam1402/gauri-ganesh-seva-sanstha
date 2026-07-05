@extends('layouts.app')

@section('title', 'Complete Your Payment — '.config('app.name'))
@section('meta_description', 'Secure payment for your donation to '.config('app.name').'.')

@section('content')
    <x-ui.section background="base">
        <div class="mx-auto max-w-lg">
            <x-ui.card>
                <div class="text-center">
                    <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">Complete Your Payment</h1>
                    <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">
                        Donating <strong class="text-primary-700 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</strong>
                        @if ($donation->campaign)
                            toward <strong>{{ $donation->campaign->name }}</strong>
                        @endif
                    </p>

                    <div class="mt-6">
                        <x-ui.button type="button" id="rzp-pay-button" variant="accent" class="w-full justify-center">Pay Securely with Razorpay</x-ui.button>
                    </div>

                    <p class="mt-4 text-xs text-text-400 dark:text-night-text-muted">
                        Payments are processed securely by Razorpay. We never see your card or bank details.
                    </p>
                </div>
            </x-ui.card>

            {{-- Posted back to us after checkout so the signature can be verified server-side. --}}
            <form id="rzp-callback-form" method="POST" action="{{ route('donations.callback', [$donation, 'razorpay']) }}" class="hidden">
                @csrf
                <input type="hidden" name="razorpay_payment_id">
                <input type="hidden" name="razorpay_order_id">
                <input type="hidden" name="razorpay_signature">
            </form>
        </div>
    </x-ui.section>
@endsection

@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            const callbackForm = document.getElementById('rzp-callback-form');

            const options = {
                key: @json($razorpayKey),
                amount: @json((int) round((float) $donation->amount * 100)),
                currency: @json($donation->currency),
                name: @json(config('app.name')),
                description: @json($donation->campaign?->name ?? 'General Donation'),
                order_id: @json($orderId),
                prefill: {
                    name: @json($donation->donor_name),
                    email: @json($donation->donor_email),
                    contact: @json($donation->donor_phone ?? ''),
                },
                theme: { color: '#8a3324' },
                handler: function (response) {
                    callbackForm.razorpay_payment_id.value = response.razorpay_payment_id;
                    callbackForm.razorpay_order_id.value = response.razorpay_order_id;
                    callbackForm.razorpay_signature.value = response.razorpay_signature;
                    callbackForm.submit();
                },
                modal: {
                    ondismiss: function () {
                        window.location.href = @json(route('donations.failed', $donation));
                    },
                },
            };

            const rzp = new Razorpay(options);

            rzp.on('payment.failed', function () {
                window.location.href = @json(route('donations.failed', $donation));
            });

            document.getElementById('rzp-pay-button').addEventListener('click', function () {
                rzp.open();
            });

            // Open checkout automatically — the button remains as a fallback.
            rzp.open();
        })();
    </script>
@endpush
