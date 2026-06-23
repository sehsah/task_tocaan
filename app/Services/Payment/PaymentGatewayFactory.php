<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Exceptions\UnsupportedGatewayException;
use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\Gateways\StripeGateway;

/**
 * Factory responsible for resolving the correct payment gateway strategy.
 *
 * HOW TO ADD A NEW GATEWAY
 * ========================
 * 1. Create a new class in App\Services\Payment\Gateways\ that implements
 *    PaymentGatewayInterface (two methods: process() and getName()).
 * 2. Add a new case to the match expression below.
 * 3. That's it — no other files need to change.
 */
class PaymentGatewayFactory
{
    /**
     * Resolve and return the gateway implementation for the given method.
     *
     * @throws UnsupportedGatewayException
     */
    public function make(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            'credit_card' => new CreditCardGateway,
            'paypal' => new PayPalGateway,
            'stripe' => new StripeGateway,
            // ↑ Add new gateways here ↑
            default => throw new UnsupportedGatewayException($method),
        };
    }

    /**
     * Return a list of all supported gateway method identifiers.
     *
     * @return list<string>
     */
    public function supported(): array
    {
        return ['credit_card', 'paypal', 'stripe'];
    }
}
