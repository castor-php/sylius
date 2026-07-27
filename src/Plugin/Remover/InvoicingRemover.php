<?php

namespace Castor\Sylius\Plugin\Remover;

use Castor\Sylius\App;
use Castor\Sylius\Util\Composer;
use Castor\Sylius\Util\Database;
use Castor\Sylius\Util\Docker;
use function Castor\io;

final readonly class InvoicingRemover implements PluginRemoverInterface
{
    public function __invoke(App $app): void
    {
        io()->title('Removing Invoicing plugin');

        Composer::allowContribRecipes($app);
        Database::rollbackPluginMigrations($app, 'Sylius\InvoicingPlugin\Migrations');
        Docker::run($app, 'composer remove sylius/invoicing-plugin');
    }
}
