<?php

namespace Tests\Unit;

use App\Services\Payment\Exceptions\UnsupportedGatewayException;
use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\PaymentGatewayFactory;
use PHPUnit\Framework\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    private PaymentGatewayFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PaymentGatewayFactory;
    }

    public function test_makes_credit_card_gateway(): void
    {
        $gateway = $this->factory->make('credit_card');
        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    public function test_makes_paypal_gateway(): void
    {
        $gateway = $this->factory->make('paypal');
        $this->assertInstanceOf(PayPalGateway::class, $gateway);
    }

    public function test_makes_stripe_gateway(): void
    {
        $gateway = $this->factory->make('stripe');
        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    public function test_throws_for_unsupported_gateway(): void
    {
        $this->expectException(UnsupportedGatewayException::class);
        $this->factory->make('bitcoin');
    }

    public function test_supported_returns_all_gateways(): void
    {
        $supported = $this->factory->supported();
        $this->assertContains('credit_card', $supported);
        $this->assertContains('paypal', $supported);
        $this->assertContains('stripe', $supported);
    }
}
