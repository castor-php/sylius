<?php

use Castor\Sylius\Attribute\AsPaymentGatewayInstaller;
use function Castor\io;

#[AsPaymentGatewayInstaller(name: 'test_payment_gateway_with_class')]
class TestPaymentGatewayInstaller
{
    public function __invoke(): void
    {
        io()->success('New payment gateway installer using a custom class is ok');
    }
}
