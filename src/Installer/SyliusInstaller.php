<?php

declare(strict_types=1);

namespace Castor\Sylius\Installer;

use Castor\Docker\Installer\AbstractServiceInstaller;
use Castor\Docker\Installer\Ast\ServiceStatementBuilder;
use Castor\Docker\Installer\Input;
use Castor\Docker\Installer\InputType;
use Castor\Docker\Installer\NeedsDatabase;
use Castor\Docker\Service\PhpMode;
use Castor\Docker\Service\ServiceInterface;
use Castor\Sylius\Service\SyliusService;

use function Castor\context;
use function Castor\Docker\docker_compose_run;

final class SyliusInstaller extends AbstractServiceInstaller implements NeedsDatabase
{
    public function getName(): string
    {
        return 'sylius';
    }

    public function getDescription(): string
    {
        return 'Sylius application';
    }

    public function getInputs(): array
    {
        return [
            new Input('name', 'Application name', InputType::Text, 'app'),
            new Input('directory', 'Directory (relative to castor.php)', InputType::Text, static fn(array $answers): string => (string) ($answers['name'] ?? 'app')),
            new Input('version', 'PHP version', InputType::Text, '8.5'),
            new Input('mode', 'Runtime', InputType::Choice, PhpMode::FrankenPhp->value, [PhpMode::FrankenPhp->value, PhpMode::Fpm->value]),
            new Input('domain', 'Domain', InputType::Text, static fn(array $answers): string => \sprintf('%s.%s', $answers['name'] ?? 'app', context()->data['root_domain'] ?? 'castor.local')),
            new Input('sylius_version', 'Sylius version (empty for latest)', InputType::Text, ''),
        ];
    }

    public function buildStatements(ServiceStatementBuilder $builder, array $answers): void
    {
        // TODO: Implement buildStatements() method.
    }

    public function createInstance(array $answers): ServiceInterface
    {
        return (new SyliusService((string) $answers['name']))
            ->withDirectory(context()->workingDirectory . '/' . $answers['directory'])
        ;
    }

    public function scaffold(array $answers): void
    {
        $version = (string) $answers['sylius_version'];
        $package = 'sylius/sylius-standard' . ($version !== '' ? ':' . $version : '');

        docker_compose_run(
            \sprintf('composer create-project %s . --no-interaction', $package),
            service: $answers['name'] . '-builder',
            workDir: '/var/www',
        );
    }
}
