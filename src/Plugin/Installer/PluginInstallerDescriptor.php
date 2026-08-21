<?php

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\Attribute\AsPluginInstaller;

final readonly class PluginInstallerDescriptor
{
    public function __construct(
        public AsPluginInstaller $attribute,
        public \ReflectionFunction|PluginInstallerInterface $installer,
    ) {}
}
