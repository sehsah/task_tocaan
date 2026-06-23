<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * Stripe payment gateway (simulated).
 *
 * Simulation rule:
 *   - Always returns successful (Stripe "test mode" always passes)
 *   - Override via env STRIPE_GATEWAY_FORCE_STATUS (successful|failed|pending)
 */
class StripeGateway implements PaymentGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(array $paymentData): array
    {
        $forced = config('payment-gateways.stripe.force_status');
        $status = $forced ?? 'successful';

        return [
            'gateway' => $this->getName(),
            'status' => $status,
            'transaction_id' => 'STR-'.strtoupper(uniqid()),
            'message' => $status === 'successful'
                ? 'Stripe payment authorised and captured.'
                : 'Stripe payment failed.',
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'stripe';
    }
}
