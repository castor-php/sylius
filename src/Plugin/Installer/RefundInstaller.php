<?php

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\App;
use Castor\Sylius\Util\Composer;
use Castor\Sylius\Util\Database;
use Castor\Sylius\Util\Docker;
use Castor\Sylius\Util\Symfony;
use function Castor\fs;
use function Castor\io;

final readonly class RefundInstaller implements PluginInstallerInterface
{
    public function __invoke(App $app): void
    {
        io()->title('Adding Refund Plugin');

        Composer::allowContribRecipes($app);
        Docker::run($app, 'composer require sylius/refund-plugin');
        Database::migrate($app);
        Symfony::cacheClear($app);
    }
}
