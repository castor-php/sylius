<?php

namespace Castor\Sylius\Plugin\Remover;

use Castor\Sylius\App;
use Castor\Sylius\Util\Assets;
use Castor\Sylius\Util\Composer;
use Castor\Sylius\Util\Database;
use Castor\Sylius\Util\Docker;
use Castor\Sylius\Util\Symfony;
use function Castor\io;

final readonly class CmsRemover implements PluginRemoverInterface
{
    public function __invoke(App $app): void
    {
        io()->title('Removing CMS plugin');

        Composer::allowContribRecipes($app);
        Database::rollbackPluginMigrations($app, 'Sylius\CmsPlugin\Migrations');
        Docker::run($app, 'composer remove sylius/cms-plugin');
        Assets::build($app);
        Symfony::cacheClear($app);
    }
}
