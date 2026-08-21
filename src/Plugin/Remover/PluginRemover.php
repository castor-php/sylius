<?php

namespace Castor\Sylius\Plugin\Remover;

use Castor\Sylius\App;

final readonly class PluginRemover implements PluginRemoverInterface
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
