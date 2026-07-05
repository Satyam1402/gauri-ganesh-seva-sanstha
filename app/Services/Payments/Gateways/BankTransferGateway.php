<?php

namespace App\Services\Payments\Gateways;

use App\Models\Donation;
use App\Services\Payments\GatewayInitiation;

class BankTransferGateway extends AbstractGateway
{
    public function displayName(): string
    {
        return 'Bank Transfer (NEFT / IMPS)';
    }

    public function isOnline(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        // Usable even before bank details are in .env — the instructions page
        // asks the donor to contact the office when details are missing.
        return true;
    }

    public function initiate(Donation $donation): GatewayInitiation
    {
        return GatewayInitiation::view('frontend.donations.pay-offline', [
            'donation' => $donation,
            'method' => 'bank_transfer',
            'bank' => [
                'account_name' => $this->config['account_name'] ?? null,
                'account_number' => $this->config['account_number'] ?? null,
                'ifsc' => $this->config['ifsc'] ?? null,
                'bank_name' => $this->config['bank_name'] ?? null,
                'branch' => $this->config['branch'] ?? null,
            ],
        ]);
    }
}
