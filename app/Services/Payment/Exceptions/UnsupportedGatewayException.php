<?php

namespace App\Services\Payment\Exceptions;

use RuntimeException;

class UnsupportedGatewayException extends RuntimeException
{
    public function __construct(string $method)
    {
        parent::__construct("Payment gateway [{$method}] is not supported.");
    }
}
