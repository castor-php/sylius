<?php



use Castor\Attribute\AsContext;
use Castor\Attribute\AsListener;
use Castor\Attribute\AsTask;
use Castor\Context;
use Castor\Docker\Event\RegisterServiceEvent;
use Castor\Docker\Service\PostgresService;
use Castor\Sylius\Attribute\AsPluginInstaller;
use Castor\Sylius\Attribute\AsPluginRemover;
use Castor\Sylius\Service\SyliusService;

use function Castor\import;
use function Castor\io;
use function Castor\PHPQa\php_cs_fixer;

import(__DIR__ . '/.castor/app');

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

#[AsTask(description: 'Fix CS', namespace: 'qa', name: 'cs', aliases: ['cs'])]
function qa_phpcsfixer(bool $dryRun = false): int
{
    $args = null;

    if ($dryRun) {
        $args = ['fix', '--dry-run'];
    }

    return php_cs_fixer(arguments: $args, version: '3.92.4')->getExitCode() ?? 0;
}

#[AsPluginInstaller(name: 'test_installer_with_function')]
function test_installer(): void
{
    io()->success('New installer using a custom function is ok');
}

#[AsPluginRemover(name: 'test_remover_with_function')]
function test_remover(): void
{
    io()->success('New remover using a custom function is ok');
}
