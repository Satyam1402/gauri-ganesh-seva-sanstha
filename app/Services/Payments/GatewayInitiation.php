<?php

namespace App\Services\Payments;

/**
 * Result of PaymentGatewayInterface::initiate() — either an external redirect
 * or a local view (offline instructions, hosted checkout widget, etc.).
 */
final readonly class GatewayInitiation
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        public ?string $redirectUrl,
        public ?string $view,
        public array $data,
    ) {}

    public static function redirect(string $url): self
    {
        return new self($url, null, []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function view(string $view, array $data = []): self
    {
        return new self(null, $view, $data);
    }

    public function isRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
