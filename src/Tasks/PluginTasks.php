<?php

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Castor\Sylius\App;
use Castor\Sylius\Plugin\Installer\PluginInstaller;
use Castor\Sylius\Plugin\Installer\PluginInstallerInterface;
use Castor\Sylius\Plugin\Remover\PluginRemoverInterface;
use function Castor\io;

final class PluginTasks
{
    private static array $installers = [];

    private static array $removers = [];

    public function __construct(
        private readonly string $name,
        private readonly string $directory,
    ) {
    }

    public function __invoke(): iterable
    {
        $app = new App($this->name, $this->directory);

        yield [
            'task' => new AsTask('plugin:add', 'sylius', 'Adds plugins', ['sylius:add']),
            'function' => function (#[AsRawTokens] array $plugins = []) use ($app): void {
                $installers = array_map(
                    function (callable $installer) use ($app): callable {
                        return fn() => $installer($app);
                    },
                    self::$installers,
                );

                if ([] === $plugins) {
                    $plugins = io()->choice(
                        'Which plugins would you like to install?',
                        array_keys($installers),
                        multiSelect: true,
                    );
                }

                foreach ($plugins as $plugin) {
                    if (!isset($installers[$plugin])) {
                        io()->warning(\sprintf('Unknown plugin "%s", skipping.', $plugin));

                        continue;
                    }
                    $installers[$plugin]();
                }
            },
        ];

        yield [
            'task' => new AsTask('plugin:remove', 'sylius', 'Removes plugins', ['sylius:remove']),
            'function' => function (#[AsRawTokens] array $plugins = []) use ($app): void {

                $removers = array_map(
                    function (callable $remover) use ($app): callable {
                        return fn() => $remover($app);
                    },
                    self::$removers,
                );

                if ([] === $plugins) {
                    $plugins = io()->choice(
                        'Which plugins would you like to remove?',
                        array_keys($removers),
                        multiSelect: true,
                    );
                }

                foreach ($plugins as $plugin) {
                    if (!isset($removers[$plugin])) {
                        io()->warning(\sprintf('Unknown plugin "%s", skipping.', $plugin));

                        continue;
                    }
                    $removers[$plugin]();
                }
            },
        ];
    }

    public static function addInstaller(string $name, PluginInstallerInterface $installer): void
    {
        self::$installers[$name] = $installer;
    }

    public static function addRemover(string $name, PluginRemoverInterface $remover): void
    {
        self::$removers[$name] = $remover;
    }
}
