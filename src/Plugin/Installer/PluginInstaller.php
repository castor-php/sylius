<?php

declare(strict_types=1);

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\App;

final readonly class PluginInstaller implements PluginInstallerInterface
{
    public function __construct(
        public string $name,
        public \Closure $code,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function __invoke(App $app): void
    {
        ($this->code)($app);
    }
}
