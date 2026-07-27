# Castor Sylius Plugin

A [Castor](https://castor.jolicode.com/) plugin that turns a PHP description of
your stack into a Sylius app, and gives you the tasks to drive it.

## Installation

```bash
castor composer require castor-php/sylius
```

## Usage

1. Create a `castor.php` file in your project root:

```php
<?php

use Castor\Attribute\AsContext;

use Castor\Attribute\AsListener;
use Castor\Context;
use Castor\Docker\Event\RegisterServiceEvent;
use Castor\Docker\Service\PostgresService;
use Castor\Sylius\Service\SyliusService;

#[AsContext(default: true)]
function default_context(): Context
{
    return new Context([
        'root_domain' => 'app.test',
    ]);
}

#[AsListener(RegisterServiceEvent::class)]
function register_service(RegisterServiceEvent $event): void
{
    $postgresService = new PostgresService();
    $event->addService($postgresService);

    $event->addService(
        (new SyliusService(name: 'app'))
            ->withDirectory(__DIR__ . '/app')
            ->withDockerfile(__DIR__ . '/infrastructure/docker/php/Dockerfile')
            ->withDatabaseService($postgresService)
            ->withDomain('app.test')
            ->withHttpAccess()
    );
}
```

## License

This plugin is part of the Castor project, released under the MIT license.
