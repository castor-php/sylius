<?php

use Castor\Sylius\Attribute\AsPluginInstaller;
use function Castor\io;

#[AsPluginInstaller(name: 'test_installer_with_class')]
class TestInstaller
{
    public function __invoke(): void
    {
        io()->success('New installer using a custom class is ok');
    }
}
