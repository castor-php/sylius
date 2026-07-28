<?php

namespace Castor\Sylius\Plugin\Remover;

use Castor\Sylius\App;
use Castor\Sylius\Plugin\Installer\PluginInstallerInterface;
use Castor\Sylius\Util\Composer;
use Castor\Sylius\Util\Database;
use Castor\Sylius\Util\Docker;
use function Castor\io;

final readonly class PaypalRemover implements PluginRemoverInterface
{
    public function name(): string
    {
        return 'paypal';
    }

    public function __invoke(App $app): void
    {
        io()->title('Removing Paypal plugin');

        Composer::allowContribRecipes($app);
        Database::rollbackPluginMigrations($app, 'Sylius\PayPalPlugin\Migrations');
        Docker::run($app, 'composer remove sylius/paypal-plugin');
    }
}
