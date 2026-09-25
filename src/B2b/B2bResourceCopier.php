<?php

declare(strict_types=1);

namespace Castor\Sylius\B2b;

use Castor\Sylius\App;

use function Castor\finder;
use function Castor\fs;

final readonly class B2bResourceCopier
{
    public static function copy(App $app, string $feature): void
    {
        $resourcesDir = self::resourcesDir($feature);

        foreach (finder()->files()->in($resourcesDir) as $file) {
            fs()->copy($resourcesDir . '/' . $file->getRelativePathname(), $app->directory() . '/' . $file->getRelativePathname());
        }
    }

    private static function resourcesDir(string $feature): string
    {
        return \dirname(__DIR__, 2) . '/resources/b2b/' . $feature;
    }
}
