<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Donation;
use RuntimeException;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected string $key,
        protected array $config,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function handleCallback(Donation $donation, array $payload): PaymentStatus
    {
        throw new RuntimeException("Gateway [{$this->key}] does not accept callbacks.");
    }
}
