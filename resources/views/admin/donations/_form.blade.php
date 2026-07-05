{{-- Shared donation form fields. $donation is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Donor Details</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Donor Name" name="donor_name" value="{{ old('donor_name', $donation->donor_name ?? '') }}" required :error="$errors->first('donor_name')" />
            <x-ui.input label="Email" name="donor_email" type="email" value="{{ old('donor_email', $donation->donor_email ?? '') }}" required :error="$errors->first('donor_email')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Phone" name="donor_phone" value="{{ old('donor_phone', $donation->donor_phone ?? '') }}" :error="$errors->first('donor_phone')" />
            <x-ui.input label="PAN (for 80G receipts)" name="pan_number" value="{{ old('pan_number', $donation->pan_number ?? '') }}" helper="Format: ABCDE1234F" :error="$errors->first('pan_number')" />
        </div>

        <x-ui.input label="Address" name="donor_address" value="{{ old('donor_address', $donation->donor_address ?? '') }}" :error="$errors->first('donor_address')" />

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous', $donation->is_anonymous ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Donor wishes to remain anonymous on the website
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Payment Details</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.input label="Amount (₹)" name="amount" type="number" step="0.01" min="1" value="{{ old('amount', $donation->amount ?? '') }}" required :error="$errors->first('amount')" />
            <x-ui.select
                label="Campaign"
                name="donation_campaign_id"
                :options="['' => 'General Donation'] + $campaigns->pluck('name', 'id')->all()"
                :selected="old('donation_campaign_id', $donation->donation_campaign_id ?? '')"
                :error="$errors->first('donation_campaign_id')"
            />
            <x-ui.input label="Donation Date" name="donated_at" type="date" value="{{ old('donated_at', isset($donation) && $donation ? $donation->donated_at->toDateString() : now()->toDateString()) }}" required :error="$errors->first('donated_at')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.select
                label="Payment Method"
                name="payment_method"
                :options="$methods"
                :selected="old('payment_method', $donation->payment_method?->value ?? 'bank_transfer')"
                :error="$errors->first('payment_method')"
            />
            <x-ui.select
                label="Payment Status"
                name="payment_status"
                :options="$statuses"
                :selected="old('payment_status', $donation->payment_status?->value ?? 'completed')"
                :error="$errors->first('payment_status')"
            />
            <x-ui.input label="Transaction / UTR ID" name="transaction_id" value="{{ old('transaction_id', $donation->transaction_id ?? '') }}" :error="$errors->first('transaction_id')" />
        </div>

        <div>
            <label for="remarks" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" class="{{ $textareaClasses }}">{{ old('remarks', $donation->remarks ?? '') }}</textarea>
            @error('remarks')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="send_emails" value="1" @checked(old('send_emails', true)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Send receipt &amp; thank-you emails when this donation is marked completed
        </label>
    </div>
</x-ui.card>
