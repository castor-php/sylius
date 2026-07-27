<?php

namespace Castor\Sylius\Plugin\Installer;

use Castor\Sylius\App;
use Castor\Sylius\Util\Assets;
use Castor\Sylius\Util\Composer;
use Castor\Sylius\Util\Docker;
use Castor\Sylius\Util\Symfony;
use function Castor\fs;
use function Castor\io;

final readonly class StripeInstaller implements PluginInstallerInterface
{
    public function __invoke(App $app): void
    {
        io()->title('Adding Stripe Plugin');

        Composer::allowContribRecipes($app);
        Docker::run($app, 'composer require flux-se/sylius-stripe-plugin');
        Docker::run($app, 'yarn install');
        Assets::build($app);
        Symfony::cacheClear($app);
    }
}
