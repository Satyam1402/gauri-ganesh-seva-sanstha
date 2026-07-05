<?php

namespace App\Services\Payments\Gateways;

use App\Models\Donation;
use App\Services\Payments\GatewayInitiation;

class UpiGateway extends AbstractGateway
{
    public function displayName(): string
    {
        return 'UPI (Google Pay / PhonePe / BHIM)';
    }

    public function isOnline(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['vpa']);
    }

    public function initiate(Donation $donation): GatewayInitiation
    {
        $vpa = $this->config['vpa'];
        $payee = $this->config['payee_name'] ?? config('app.name');

        // Standard UPI deep link — opens any UPI app on mobile with the
        // amount and payee prefilled.
        $upiLink = 'upi://pay?'.http_build_query([
            'pa' => $vpa,
            'pn' => $payee,
            'am' => number_format((float) $donation->amount, 2, '.', ''),
            'cu' => $donation->currency,
            'tn' => 'Donation '.$donation->reference,
        ]);

        return GatewayInitiation::view('frontend.donations.pay-offline', [
            'donation' => $donation,
            'method' => 'upi',
            'upi' => [
                'vpa' => $vpa,
                'payee_name' => $payee,
                'link' => $upiLink,
            ],
        ]);
    }
}
