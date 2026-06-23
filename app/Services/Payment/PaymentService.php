<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Exceptions\DuplicatePaymentException;
use App\Services\Payment\Exceptions\UnsupportedGatewayException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
    ) {}

    /**
     * Process a payment for the given order.
     *
     * If a payment with the same idempotency key already exists, it is
     * returned immediately without hitting the gateway again — this makes
     * the endpoint safe to retry on network failure.
     *
     * @param  string  $method  Gateway identifier (credit_card, paypal, stripe, …)
     * @param  string  $idempotencyKey  Caller-supplied unique key for this attempt
     *
     * @throws DuplicatePaymentException  when the idempotency key was already used
     * @throws UnsupportedGatewayException
     * @throws \RuntimeException  if the order is not in "confirmed" status
     */
    public function processPayment(Order $order, string $method, string $idempotencyKey): Payment
    {
        // --- Idempotency check -------------------------------------------
        $existing = Payment::where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            throw new DuplicatePaymentException($existing);
        }

        // --- Business rule guard -----------------------------------------
        if (! $order->canBeCharged()) {
            throw new \RuntimeException(
                'Payment can only be processed for orders in [confirmed] status. '
                ."Current status: [{$order->status}]."
            );
        }

        // --- Gateway processing ------------------------------------------
        $gateway         = $this->gatewayFactory->make($method);
        $gatewayResponse = $gateway->process([
            'order_id'    => $order->id,
            'order_total' => $order->total,
        ]);

        return DB::transaction(function () use ($order, $method, $idempotencyKey, $gatewayResponse) {
            return Payment::create([
                'order_id'         => $order->id,
                'idempotency_key'  => $idempotencyKey,
                'payment_method'   => $method,
                'status'           => $gatewayResponse['status'],
                'gateway_response' => $gatewayResponse,
                'processed_at'     => now(),
            ]);
        });
    }
}
