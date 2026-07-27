<?php

namespace Castor\Sylius\Plugin\Remover;

use Castor\Sylius\App;

interface PluginRemoverInterface
{
    public function __invoke(App $app): void;
}
