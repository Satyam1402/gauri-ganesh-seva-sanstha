@extends('layouts.app')

@section('title', ($campaign ? 'Donate to '.$campaign->name : 'Donate').' — '.config('app.name'))
@section('meta_description', 'Make a secure donation to '.config('app.name').($campaign ? ' for '.$campaign->name : '').'. Online and offline payment options available.')
@section('canonical_url', route('donations.donate', $campaign))
@section('og_title', ($campaign ? 'Donate to '.$campaign->name : 'Donate').' — '.config('app.name'))
@section('og_description', 'Your generosity powers our seva. Donate securely in under a minute.')

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Campaigns', 'url' => route('donations.campaigns.index')],
            ['label' => 'Donate'],
        ]" class="mb-4" />

        <x-ui.section-heading
            :heading="$campaign ? 'Donate to '.$campaign->name : 'Make a Donation'"
            subheading="Every contribution — big or small — helps us serve the community."
        />
    </x-ui.section>

    <x-ui.section background="base">
        <div class="mx-auto max-w-2xl">
            @if (empty($gatewayOptions))
                <x-ui.alert type="warning">
                    Online donations are temporarily unavailable. Please contact us directly to donate.
                </x-ui.alert>
            @else
                <form
                    method="POST"
                    action="{{ route('donations.store') }}"
                    class="space-y-6"
                    x-data="{ amount: '{{ old('amount', '') }}' }"
                >
                    @csrf

                    @if ($campaign)
                        <input type="hidden" name="donation_campaign_id" value="{{ $campaign->id }}">
                    @endif

                    {{-- Honeypot field — hidden from humans, catnip for bots. --}}
                    <div class="hidden" aria-hidden="true">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Donation Amount</h3>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($suggestedAmounts as $suggested)
                                <button
                                    type="button"
                                    @click="amount = '{{ $suggested }}'"
                                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                                    :class="amount == '{{ $suggested }}' ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted'"
                                >
                                    ₹{{ number_format($suggested) }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <label for="amount" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Amount (₹)</label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                x-model="amount"
                                min="{{ config('donations.min_amount') }}"
                                step="1"
                                required
                                placeholder="Enter amount"
                                class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text"
                            >
                            @error('amount')
                                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (! $campaign && $campaigns->isNotEmpty())
                            <div class="mt-4">
                                <x-ui.select
                                    label="Donate Toward (optional)"
                                    name="donation_campaign_id"
                                    :options="['' => 'General Donation — where it\'s needed most'] + $campaigns->pluck('name', 'id')->all()"
                                    :selected="old('donation_campaign_id', '')"
                                    :error="$errors->first('donation_campaign_id')"
                                />
                            </div>
                        @endif
                    </x-ui.card>

                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Your Details</h3>

                        <div class="mt-4 space-y-5">
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <x-ui.input label="Full Name" name="donor_name" value="{{ old('donor_name') }}" required :error="$errors->first('donor_name')" />
                                <x-ui.input label="Email" name="donor_email" type="email" value="{{ old('donor_email') }}" required helper="Your receipt will be emailed here." :error="$errors->first('donor_email')" />
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <x-ui.input label="Phone (optional)" name="donor_phone" value="{{ old('donor_phone') }}" :error="$errors->first('donor_phone')" />
                                <x-ui.input label="PAN (optional)" name="pan_number" value="{{ old('pan_number') }}" helper="Needed for 80G tax benefit. Format: ABCDE1234F" :error="$errors->first('pan_number')" />
                            </div>

                            <x-ui.input label="Address (optional)" name="donor_address" value="{{ old('donor_address') }}" :error="$errors->first('donor_address')" />

                            <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                                <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous')) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                                Keep my name hidden from public supporter lists
                            </label>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Payment Method</h3>

                        <div class="mt-4 space-y-3">
                            @foreach ($gatewayOptions as $key => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-md border border-border-subtle px-4 py-3 text-sm hover:border-primary-700 dark:border-night-border">
                                    <input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($gatewayOptions)) === $key) class="border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                                    <span class="font-medium text-text-900 dark:text-night-text">{{ $label }}</span>
                                </label>
                            @endforeach
                            @error('payment_method')
                                <p class="text-xs text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </x-ui.card>

                    <x-ui.button type="submit" variant="accent" class="w-full justify-center">Proceed to Donate</x-ui.button>

                    <p class="text-center text-xs text-text-400 dark:text-night-text-muted">
                        Your details are used only for issuing your donation receipt and are never shared.
                    </p>
                </form>
            @endif
        </div>
    </x-ui.section>
@endsection
