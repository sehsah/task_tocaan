<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * Credit Card payment gateway (simulated).
 *
 * Simulation rule:
 *   - Orders with a total that is an even number → successful
 *   - Orders with a total that is an odd number  → failed
 *   - Override via env CREDIT_CARD_GATEWAY_FORCE_STATUS (successful|failed|pending)
 */
class CreditCardGateway implements PaymentGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(array $paymentData): array
    {
        // Allow environment override for testing / demonstration
        $forced = config('payment-gateways.credit_card.force_status');

        if ($forced !== null) {
            $status = $forced;
        } else {
            // Simulate: even total → success, odd total → failed
            $total = (float) ($paymentData['order_total'] ?? 0);
            $status = ((int) $total % 2 === 0) ? 'successful' : 'failed';
        }

        return [
            'gateway' => $this->getName(),
            'status' => $status,
            'transaction_id' => 'CC-'.strtoupper(uniqid()),
            'message' => $status === 'successful'
                ? 'Credit card payment processed successfully.'
                : 'Credit card payment declined.',
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'credit_card';
    }
}
