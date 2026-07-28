<?php

namespace Castor\Sylius\Service;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Castor\Docker\Service\SymfonyService;
use Castor\Sylius\Tasks\MenuTasks;
use Castor\Sylius\Tasks\PluginTasks;
use function Castor\Docker\docker_compose_run;

class SyliusService extends SymfonyService
{
    private iterable $tasks = [];

    public function getTasks(): iterable
    {
        yield from parent::getTasks();

        yield from (new PluginTasks($this->name, $this->getDirectory()))();
        yield from (new MenuTasks($this->name, $this->getDirectory()))();

        yield from $this->tasks;

        yield [
            'task' => new AsTask('fixtures', $this->name . ':db', 'Loads fixtures', ['sylius:fixtures']),
            'function' => function (#[AsRawTokens] array $rawTokens = []): void {
                docker_compose_run(sprintf('php bin/console sylius:fixture:load %s', implode(' ', $rawTokens)), $this->name . '-builder');
            },
        ];
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
