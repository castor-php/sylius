<?php

declare(strict_types=1);

namespace Castor\Sylius\Service;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Castor\Docker\Service\SymfonyService;
use Castor\Sylius\App;
use Castor\Sylius\Tasks\ImportTasks;
use Castor\Sylius\Tasks\MenuTasks;
use Castor\Sylius\Tasks\PluginTasks;
use Castor\Sylius\Util\Fixtures;

class SyliusService extends SymfonyService
{
    private iterable $tasks = [];

    public function __construct(string $name = 'app')
    {
        parent::__construct($name);

        $this->addExtension('gd');
        $this->addExtension('imagick');
        $this->addExtension('exif');
    }

    public function getTasks(): iterable
    {
        yield from parent::getTasks();

        yield from (new PluginTasks($this->name, $this->getDirectory()))();
        yield from (new MenuTasks($this->name, $this->getDirectory()))();
        yield from (new ImportTasks($this->name, $this->getDirectory()))();

        yield from $this->tasks;

        yield [
            'task' => new AsTask('fixtures', $this->name . ':db', 'Loads fixtures', ['sylius:fixtures']),
            /**
             * @param list<string> $rawTokens
             */
            'function' => function (#[AsRawTokens] array $rawTokens = []): void {
                $app = new App($this->getName(), $this->getDirectory());
                Fixtures::load($app, ...$rawTokens);
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
