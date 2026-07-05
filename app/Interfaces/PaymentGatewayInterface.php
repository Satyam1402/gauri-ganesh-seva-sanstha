<?php

namespace App\Interfaces;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Services\Payments\GatewayInitiation;

/**
 * Contract every payment gateway driver must implement.
 *
 * To add a new gateway: implement this interface, register the driver class
 * under a new key in config/donations.php, and (if online) add a callback
 * branch view — the donate flow itself never changes.
 */
interface PaymentGatewayInterface
{
    /**
     * The config key this gateway was registered under (e.g. "razorpay").
     */
    public function key(): string;

    /**
     * Human-readable name shown to donors on the donate form.
     */
    public function displayName(): string;

    /**
     * Online gateways confirm payment via callback; offline gateways leave
     * the donation pending until an admin verifies it manually.
     */
    public function isOnline(): bool;

    /**
     * Whether the gateway has the configuration it needs to take payments.
     */
    public function isConfigured(): bool;

    /**
     * Begin payment for a pending donation. Returns either a redirect URL or
     * a view + data the payment page should render.
     */
    public function initiate(Donation $donation): GatewayInitiation;

    /**
     * Process the gateway's callback payload and return the resulting status.
     * Offline gateways never receive callbacks and may throw.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(Donation $donation, array $payload): PaymentStatus;
}
