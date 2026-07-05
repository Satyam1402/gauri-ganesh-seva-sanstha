<?php

namespace App\Services\Payments\Gateways;

use App\Models\Donation;
use App\Services\Payments\GatewayInitiation;
use RuntimeException;

/**
 * Extension point for PayPal Checkout. Disabled by default (PAYPAL_ENABLED).
 *
 * To activate: create an order via the PayPal Orders v2 REST API in
 * initiate() (returning GatewayInitiation::redirect($approveLink)) and
 * capture it in handleCallback(). The rest of the donation flow already
 * handles redirects, callbacks, and status transitions.
 */
class PaypalGateway extends AbstractGateway
{
    public function displayName(): string
    {
        return 'PayPal';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['client_id']) && ! empty($this->config['secret']);
    }

    public function initiate(Donation $donation): GatewayInitiation
    {
        throw new RuntimeException(
            'PayPal checkout is not implemented yet. Complete PaypalGateway::initiate() using the Orders v2 API.'
        );
    }
}
