<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Razorpay = 'razorpay';
    case Stripe = 'stripe';
    case Paypal = 'paypal';
    case BankTransfer = 'bank_transfer';
    case Upi = 'upi';

    public function label(): string
    {
        return match ($this) {
            self::Razorpay => 'Razorpay',
            self::Stripe => 'Stripe',
            self::Paypal => 'PayPal',
            self::BankTransfer => 'Bank Transfer',
            self::Upi => 'UPI',
        };
    }

    /**
     * Offline methods collect payment outside the site; the donation stays
     * pending until an admin verifies it and marks it completed.
     */
    public function isOffline(): bool
    {
        return match ($this) {
            self::BankTransfer, self::Upi => true,
            default => false,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->value, self::cases()),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
