<?php

declare(strict_types=1);

namespace Castor\Sylius\Util;

use Castor\Sylius\App;
use function Castor\fs;

final readonly class Yaml
{
    public static function uncommentBlock(App $app, string $file, string $block): void
    {
        $realFile = $app->directory() . '/' . $file;

        $content = fs()->readFile($realFile);

        $lines = explode("\n", trim($block));

        $pattern = implode(
            '\R',
            array_map(
                static fn (string $line): string => \sprintf(
                    '[ \t]*#%s',
                    preg_quote($line, '/'),
                ),
                $lines,
            ),
        );

        $replaced = preg_replace_callback(
            \sprintf('/%s/m', $pattern),
            static function (array $matches): string {
                return preg_replace(
                    '/^([ \t]*)#/m',
                    '$1',
                    $matches[0],
                ) ?? $matches[0];
            },
            $content,
            1,
        );

        if (null !== $replaced) {
            $content = $replaced;
        }

        fs()->dumpFile($realFile, $content);
    }

    public static function appendToSection(
        App $app,
        string $file,
        string $section,
        string $block,
    ): void {
        $realFile = $app->directory() . '/' . $file;

        $content = fs()->readFile($realFile);

        $trimmedBlock = preg_replace('/^\R+|\R+$/', '', $block);

        if (null === $trimmedBlock) {
            return;
        }

        $block = $trimmedBlock;

        if (str_contains($content, $block)) {
            return;
        }

        $pattern = \sprintf(
            '/^%s:\R(.*?)(?=^[^\s]|\z)/ms',
            preg_quote($section, '/'),
        );

        $replaced = preg_replace_callback(
            $pattern,
            static function (array $matches) use ($section, $block): string {
                return \sprintf(
                    "%s:\n%s\n\n%s\n",
                    $section,
                    rtrim($matches[1]),
                    $block,
                );
            },
            $content,
            1,
        );

        if (null !== $replaced) {
            $content = $replaced;
        }

        fs()->dumpFile($realFile, $content);
    }
}
