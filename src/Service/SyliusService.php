<?php

namespace Castor\Sylius\Service;

use Castor\Docker\Service\SymfonyService;
use Castor\Sylius\Tasks\MenuTasks;
use Castor\Sylius\Tasks\PluginTasks;

class SyliusService extends SymfonyService
{
    private iterable $tasks = [];

    public function getTasks(): iterable
    {
        yield from parent::getTasks();

        yield from (new PluginTasks($this->name, $this->getDirectory()))();
        yield from (new MenuTasks($this->name, $this->getDirectory()))();

        yield from $this->tasks;
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
