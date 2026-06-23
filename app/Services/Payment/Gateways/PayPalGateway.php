<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * PayPal payment gateway (simulated).
 *
 * Simulation rule:
 *   - Orders with a total < 1000 → successful
 *   - Orders with a total >= 1000 → failed (exceeds simulated limit)
 *   - Override via env PAYPAL_GATEWAY_FORCE_STATUS (successful|failed|pending)
 */
class PayPalGateway implements PaymentGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(array $paymentData): array
    {
        $forced = config('payment-gateways.paypal.force_status');

        if ($forced !== null) {
            $status = $forced;
        } else {
            $total = (float) ($paymentData['order_total'] ?? 0);
            $status = ($total < 1000) ? 'successful' : 'failed';
        }

        return [
            'gateway' => $this->getName(),
            'status' => $status,
            'transaction_id' => 'PP-'.strtoupper(uniqid()),
            'message' => $status === 'successful'
                ? 'PayPal payment completed successfully.'
                : 'PayPal payment failed: amount exceeds limit.',
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'paypal';
    }
}
