<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Services\Payments\GatewayInitiation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Razorpay via its REST API and hosted Checkout widget — no SDK dependency.
 * Signature verification follows the documented HMAC-SHA256 scheme.
 */
class RazorpayGateway extends AbstractGateway
{
    private const API_BASE = 'https://api.razorpay.com/v1';

    public function displayName(): string
    {
        return 'Razorpay (Cards / UPI / Netbanking)';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['key']) && ! empty($this->config['secret']);
    }

    public function initiate(Donation $donation): GatewayInitiation
    {
        $orderId = $donation->meta['razorpay_order_id'] ?? null;

        if (! $orderId) {
            $response = Http::withBasicAuth($this->config['key'], $this->config['secret'])
                ->post(self::API_BASE.'/orders', [
                    'amount' => (int) round((float) $donation->amount * 100),
                    'currency' => $donation->currency,
                    'receipt' => $donation->reference,
                    'notes' => ['donation_reference' => $donation->reference],
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Unable to create Razorpay order: '.$response->body());
            }

            $orderId = $response->json('id');

            $donation->mergeMeta(['razorpay_order_id' => $orderId]);
            $donation->save();
        }

        return GatewayInitiation::view('frontend.donations.pay-razorpay', [
            'donation' => $donation,
            'razorpayKey' => $this->config['key'],
            'orderId' => $orderId,
        ]);
    }

    public function handleCallback(Donation $donation, array $payload): PaymentStatus
    {
        $orderId = $donation->meta['razorpay_order_id'] ?? null;
        $paymentId = $payload['razorpay_payment_id'] ?? null;
        $signature = $payload['razorpay_signature'] ?? null;

        if (! $orderId || ! $paymentId || ! $signature) {
            return PaymentStatus::Failed;
        }

        $expected = hash_hmac('sha256', "{$orderId}|{$paymentId}", $this->config['secret']);

        if (! hash_equals($expected, (string) $signature)) {
            return PaymentStatus::Failed;
        }

        $donation->transaction_id = $paymentId;
        $donation->mergeMeta(['razorpay_signature' => $signature]);

        return PaymentStatus::Completed;
    }
}
