<?php

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\App;
use function Castor\fs;
use function Castor\io;

final readonly class PluginInstaller implements PluginInstallerInterface
{
    public function __construct(
        public string $name,
        public \Closure $code,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function __invoke(App $app): void
    {
        ($this->code)($app);
    }
}
