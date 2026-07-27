<?php

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\App;
use function Castor\fs;
use function Castor\io;

final class PluginInstaller implements PluginInstallerInterface
{
    public function __construct(
        public \Closure $code,
    ) {
    }

    public function __invoke(App $app): void
    {
        ($this->code)($app);
    }
}
