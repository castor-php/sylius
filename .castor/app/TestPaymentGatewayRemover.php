<?php

use Castor\Sylius\Attribute\AsPaymentGatewayRemover;
use function Castor\io;

#[AsPaymentGatewayRemover(name: 'test_payment_gateway_with_class')]
class TestPaymentGatewayRemover
{
    public function __invoke(): void
    {
        io()->success('New payment gateway remover using a custom class is ok');
    }
}
