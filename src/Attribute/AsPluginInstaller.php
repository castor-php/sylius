<?php

namespace Castor\Sylius\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION)]
class AsPluginInstaller
{
    public function __construct(
        public string $name,
    ) {
    }
}
