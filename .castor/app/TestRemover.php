<?php

use Castor\Sylius\Attribute\AsPluginRemover;
use function Castor\io;

#[AsPluginRemover(name: 'test_remover_with_class')]
class TestRemover
{
    public function __invoke(): void
    {
        io()->success('New remover using a custom class is ok');
    }
}
