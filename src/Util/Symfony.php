<?php

declare(strict_types=1);

namespace Castor\Sylius\Util;

use Castor\Sylius\App;
use function Castor\fs;

final readonly class Symfony
{
    public static function cacheClear(App $app, bool $warm = true): void
    {
        Docker::run($app, 'rm -rf var/cache');

        if ($warm) {
            Docker::run($app, 'php bin/console cache:warm');
        }
    }

    /**
     * @param array<string, bool> $envs
     */
    public static function addBundle(
        App $app,
        string $bundle,
     array $envs = ['all' => true],
     string $file = 'config/bundles.php',
    ): void {
        $realFile = $app->directory() . '/' . $file;

        $content = fs()->readFile($realFile);

        if (str_contains($content, "{$bundle}::class")) {
            return;
        }

        $envs = \sprintf(
            '[%s]',
            implode(
                ', ',
                array_map(
                    static fn (string $env, bool $enabled): string => \sprintf("'%s' => %s", $env, $enabled ? 'true' : 'false'),
                    array_keys($envs),
                    $envs,
                ),
            ),
        );

        $entry = \sprintf(
            '    %s::class => %s,',
            $bundle,
            $envs,
        );

        $replaced = preg_replace(
            '/\n\];\s*$/',
            "\n{$entry}\n];",
            $content,
            1,
        );

        if (null !== $replaced) {
            $content = $replaced;
        }

        fs()->dumpFile($realFile, $content);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function addJsController(
        App $app,
        string $package,
        string $controller,
        array $config,
        string $file = 'assets/controllers.json',
    ): void {
        $realFile = $app->directory() . '/' . $file;

        if (!file_exists($realFile)) {
            throw new \RuntimeException(\sprintf('File "%s" not found.', $file));
        }

        $content = fs()->readFile($realFile);

        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        $data['controllers'] ??= [];
        $data['controllers'][$package] ??= [];
        $data['controllers'][$package][$controller] = $config;

        fs()->dumpFile(
            $realFile,
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ) . PHP_EOL,
        );
    }
}
