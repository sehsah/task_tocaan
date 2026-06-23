<?php

namespace App\Services\Payment\Exceptions;

use App\Models\Payment;
use RuntimeException;

/**
 * Thrown when a payment request arrives with an idempotency key that was
 * already used for a previously completed (or attempted) payment.
 *
 * The controller should respond with the original payment (HTTP 200),
 * NOT create a new one.
 */
class DuplicatePaymentException extends RuntimeException
{
    public function __construct(
        private readonly Payment $existingPayment,
    ) {
        parent::__construct(
            "Payment with idempotency key [{$existingPayment->idempotency_key}] already exists."
        );
    }

    /**
     * Return the original payment that was created for this idempotency key.
     */
    public function getExistingPayment(): Payment
    {
        return $this->existingPayment;
    }
}
