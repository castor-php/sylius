<?php

declare(strict_types=1);

namespace Castor\Sylius\Service;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Castor\Context;
use Castor\Docker\Service\Builder\ComposeBuilder;
use Castor\Docker\Service\PhpMode;
use Castor\Docker\Service\SymfonyService;
use Castor\Sylius\App;
use Castor\Sylius\Import\ImportPaths;
use Castor\Sylius\Tasks\ImportTasks;
use Castor\Sylius\Tasks\MenuTasks;
use Castor\Sylius\Tasks\PluginTasks;

use function Castor\Docker\docker_compose_run;
use function Castor\Sylius\Import\ensure_import_scaffold;

class SyliusService extends SymfonyService
{
    private iterable $tasks = [];

    public function __construct(string $name = 'app')
    {
        parent::__construct($name);

        // LiipImagine uses GD, which is not safe with FrankenPHP's persistent PHP
        // threads (zend_mm_heap corrupted under concurrent WebP thumbnail work).
        $this->withMode(PhpMode::Fpm);

        $this->addExtension('gd');
        $this->addExtension('exif');
    }

    public function updateCompose(Context $context, ComposeBuilder $builder): ComposeBuilder
    {
        $builder = parent::updateCompose($context, $builder);

        $builder->service($this->name)->volume(ImportPaths::VAR_HOST_DIR, ImportPaths::VAR_CONTAINER_DIR);

        $builderServiceName = $this->resolveBuilderServiceName();
        if ($builderServiceName !== $this->name) {
            $builder->service($builderServiceName)->volume(ImportPaths::VAR_HOST_DIR, ImportPaths::VAR_CONTAINER_DIR);
        }

        return $builder;
    }

    private function resolveBuilderServiceName(): string
    {
        if (method_exists(parent::class, 'getBuilderServiceName')) {
            return $this->getBuilderServiceName();
        }

        return $this->name . '-builder';
    }

    public function getTasks(): iterable
    {
        yield from parent::getTasks();

        yield from (new PluginTasks($this->name, $this->getDirectory()))();
        yield from (new MenuTasks($this->name, $this->getDirectory()))();
        yield from (new ImportTasks($this->name, $this->getDirectory()))();

        yield from $this->tasks;

        yield [
            'task' => new AsTask('build', $this->name . ':assets', 'Install and build frontend assets'),
            'function' => function (): void {
                docker_compose_run('yarn install', $this->name . '-builder');
                docker_compose_run('yarn build', $this->name . '-builder');
            },
        ];

        yield [
            'task' => new AsTask('fixtures', $this->name . ':db', 'Loads fixtures', ['sylius:fixtures']),
            'function' => function (#[AsRawTokens] array $rawTokens = []): void {
                ensure_import_scaffold(new App($this->name, $this->getDirectory()), $this->name);
                docker_compose_run(self::fixturesLoadCommand($rawTokens), $this->name . '-builder');
            },
        ];
    }

    /**
     * @param list<string> $rawTokens
     */
    public static function fixturesLoadCommand(array $rawTokens = []): string
    {
        $args = trim(implode(' ', $rawTokens) . ' -n');

        return 'php bin/console sylius:fixtures:load ' . $args;
    }

    public function withTasks(iterable $tasks): self
    {
        $currentTasks = $this->tasks;

        $this->tasks = (static function () use ($currentTasks, $tasks): \Generator {
            yield from $currentTasks;
            yield from $tasks;
        })();

        return $this;
    }
}
