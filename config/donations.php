<?php

use App\Services\Payments\Gateways\BankTransferGateway;
use App\Services\Payments\Gateways\PaypalGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use App\Services\Payments\Gateways\StripeGateway;
use App\Services\Payments\Gateways\UpiGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Donation Defaults
    |--------------------------------------------------------------------------
    */

    'currency' => env('DONATION_CURRENCY', 'INR'),

    'min_amount' => (float) env('DONATION_MIN_AMOUNT', 10),

    'max_amount' => (float) env('DONATION_MAX_AMOUNT', 10000000),

    // Quick-pick amounts rendered as buttons on the public donate form.
    'suggested_amounts' => [501, 1101, 2100, 5100, 11000],

    /*
    |--------------------------------------------------------------------------
    | Receipts
    |--------------------------------------------------------------------------
    |
    | Receipt numbers look like GGSS-2026-000123 and are assigned only when a
    | donation reaches the "completed" status.
    |
    */

    'receipt_prefix' => env('DONATION_RECEIPT_PREFIX', 'GGSS'),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    // Where new-donation notifications are sent. Falls back to MAIL_FROM_ADDRESS.
    'admin_notification_email' => env('DONATION_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Every gateway implements App\Interfaces\PaymentGatewayInterface and is
    | resolved through App\Services\Payments\PaymentGatewayManager. To add a
    | new gateway, write a driver class and register it here — nothing else
    | in the donation flow needs to change.
    |
    | Only gateways with "enabled" => true are offered on the donate form.
    |
    */

    'gateways' => [

        'bank_transfer' => [
            'driver' => BankTransferGateway::class,
            'enabled' => (bool) env('DONATION_BANK_TRANSFER_ENABLED', true),
            'account_name' => env('DONATION_BANK_ACCOUNT_NAME', 'Gauri Ganesh Seva Sanstha'),
            'account_number' => env('DONATION_BANK_ACCOUNT_NUMBER'),
            'ifsc' => env('DONATION_BANK_IFSC'),
            'bank_name' => env('DONATION_BANK_NAME'),
            'branch' => env('DONATION_BANK_BRANCH'),
        ],

        'upi' => [
            'driver' => UpiGateway::class,
            'enabled' => (bool) env('DONATION_UPI_ENABLED', true),
            'vpa' => env('DONATION_UPI_VPA'),
            'payee_name' => env('DONATION_UPI_PAYEE_NAME', 'Gauri Ganesh Seva Sanstha'),
        ],

        'razorpay' => [
            'driver' => RazorpayGateway::class,
            'enabled' => (bool) env('RAZORPAY_ENABLED', false),
            'key' => env('RAZORPAY_KEY'),
            'secret' => env('RAZORPAY_SECRET'),
        ],

        'stripe' => [
            'driver' => StripeGateway::class,
            'enabled' => (bool) env('STRIPE_ENABLED', false),
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
        ],

        'paypal' => [
            'driver' => PaypalGateway::class,
            'enabled' => (bool) env('PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],

    ],

];
