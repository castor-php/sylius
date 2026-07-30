<?php

namespace Castor\Sylius\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION)]
class AsPluginRemover
{
    public function __construct(
        public string $name,
    ) {
    }
}
