<?php

declare(strict_types=1);

namespace Castor\Sylius\Util;

use Castor\Sylius\App;
use Symfony\Component\Finder\Finder;

final readonly class Filesystem
{
    public static function latestFile(App $app, string $directory): ?string
    {
        $directory = $app->directory() . '/' . $directory;

        if (!is_dir($directory)) {
            return null;
        }

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->sortByModifiedTime()
            ->reverseSorting()
        ;

        foreach ($finder as $file) {
            return $file->getRelativePathname();
        }

        return null;
    }
}
