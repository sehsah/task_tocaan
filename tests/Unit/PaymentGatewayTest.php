<?php

namespace Tests\Unit;

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\Gateways\StripeGateway;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    // -----------------------------------------------------------------------
    // CreditCardGateway
    // -----------------------------------------------------------------------

    public function test_credit_card_gateway_name(): void
    {
        $gateway = new CreditCardGateway;
        $this->assertSame('credit_card', $gateway->getName());
    }

    public function test_credit_card_gateway_returns_required_keys(): void
    {
        $gateway = new CreditCardGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 100.00]);

        $this->assertArrayHasKey('gateway', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('processed_at', $result);
    }

    public function test_credit_card_gateway_even_total_succeeds(): void
    {
        $gateway = new CreditCardGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 100.00]);
        $this->assertSame('successful', $result['status']);
    }

    public function test_credit_card_gateway_odd_total_fails(): void
    {
        $gateway = new CreditCardGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 101.00]);
        $this->assertSame('failed', $result['status']);
    }

    // -----------------------------------------------------------------------
    // PayPalGateway
    // -----------------------------------------------------------------------

    public function test_paypal_gateway_name(): void
    {
        $gateway = new PayPalGateway;
        $this->assertSame('paypal', $gateway->getName());
    }

    public function test_paypal_gateway_small_total_succeeds(): void
    {
        $gateway = new PayPalGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 500.00]);
        $this->assertSame('successful', $result['status']);
    }

    public function test_paypal_gateway_large_total_fails(): void
    {
        $gateway = new PayPalGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 1500.00]);
        $this->assertSame('failed', $result['status']);
    }

    public function test_paypal_gateway_returns_required_keys(): void
    {
        $gateway = new PayPalGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 100.00]);

        $this->assertArrayHasKey('gateway', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('transaction_id', $result);
    }

    // -----------------------------------------------------------------------
    // StripeGateway
    // -----------------------------------------------------------------------

    public function test_stripe_gateway_name(): void
    {
        $gateway = new StripeGateway;
        $this->assertSame('stripe', $gateway->getName());
    }

    public function test_stripe_gateway_always_succeeds(): void
    {
        $gateway = new StripeGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 999999.99]);
        $this->assertSame('successful', $result['status']);
    }

    public function test_stripe_gateway_returns_required_keys(): void
    {
        $gateway = new StripeGateway;
        $result = $gateway->process(['order_id' => 1, 'order_total' => 50.00]);

        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertStringStartsWith('STR-', $result['transaction_id']);
    }
}
