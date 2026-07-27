<?php

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\Attribute\AsPluginInstaller;
use function Castor\fs;
use function Castor\io;

final readonly class PluginInstallerDescriptor
{
    public function __construct(
        public AsPluginInstaller $attribute,
        public \ReflectionFunction|PluginInstallerInterface $installer,
    ) {
    }
}
