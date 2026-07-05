<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $resolved = [];

    public function gateway(string $key): PaymentGatewayInterface
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $config = config("donations.gateways.{$key}");

        if (! is_array($config) || empty($config['driver'])) {
            throw new InvalidArgumentException("Payment gateway [{$key}] is not registered.");
        }

        return $this->resolved[$key] = app($config['driver'], [
            'key' => $key,
            'config' => $config,
        ]);
    }

    /**
     * Gateways donors can actually pay through right now.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function enabled(): array
    {
        $gateways = [];

        foreach (array_keys(config('donations.gateways', [])) as $key) {
            if (! config("donations.gateways.{$key}.enabled")) {
                continue;
            }

            $gateway = $this->gateway($key);

            if ($gateway->isConfigured()) {
                $gateways[$key] = $gateway;
            }
        }

        return $gateways;
    }

    /**
     * key => display name pairs for the donate form's method selector.
     *
     * @return array<string, string>
     */
    public function enabledOptions(): array
    {
        return array_map(
            fn (PaymentGatewayInterface $gateway) => $gateway->displayName(),
            $this->enabled(),
        );
    }

    public function isEnabled(string $key): bool
    {
        return array_key_exists($key, $this->enabled());
    }
}
