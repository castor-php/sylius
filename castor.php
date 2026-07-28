<?php

use Castor\Attribute\AsContext;

use Castor\Attribute\AsListener;
use Castor\Context;
use Castor\Docker\Event\RegisterServiceEvent;
use Castor\Docker\Service\PostgresService;
use Castor\Sylius\Service\SyliusService;
use function Castor\io;

\Castor\import(__DIR__ . '/.castor/app');

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
    $postgresService = (new PostgresService())
        ->withVersion('16')
    ;
    $event->addService($postgresService);

    $event->addService(
        (new SyliusService(name: 'app'))
            ->withDirectory(__DIR__ . '/app')
            ->withDatabaseService($postgresService)
            ->withDomain('app.test')
            ->withHttpAccess()
    );
}

#[\Castor\Sylius\Attribute\AsPluginInstaller(name: 'test_function')]
function test_installer(): void
{
    io()->success('New installer using a custom function is ok');
}
