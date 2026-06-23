<?php

namespace App\Services\Payment\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Process the given payment data through this gateway.
     *
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed> Gateway response including 'status' and 'message'
     */
    public function process(array $paymentData): array;

    /**
     * Return the canonical name of this payment gateway.
     */
    public function getName(): string;
}
