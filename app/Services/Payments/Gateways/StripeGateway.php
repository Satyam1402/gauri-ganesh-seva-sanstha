<?php

namespace App\Services\Payments\Gateways;

use App\Models\Donation;
use App\Services\Payments\GatewayInitiation;
use RuntimeException;

/**
 * Extension point for Stripe Checkout. Disabled by default (STRIPE_ENABLED).
 *
 * To activate: `composer require stripe/stripe-php`, create a Checkout
 * Session in initiate() (returning GatewayInitiation::redirect($session->url))
 * and verify the session in handleCallback(). The rest of the donation flow
 * already handles redirects, callbacks, and status transitions.
 */
class StripeGateway extends AbstractGateway
{
    public function displayName(): string
    {
        return 'Stripe (International Cards)';
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
        throw new RuntimeException(
            'Stripe checkout is not implemented yet. Install stripe/stripe-php and complete StripeGateway::initiate().'
        );
    }
}
