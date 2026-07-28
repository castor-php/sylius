<?php

namespace Castor\Sylius\Plugin\Remover;

use Castor\Sylius\App;
use Castor\Sylius\Util\Composer;
use Castor\Sylius\Util\Docker;
use function Castor\io;

final readonly class BugSnagRemover implements PluginRemoverInterface
{
    public function name(): string
    {
        return 'bugsnag';
    }

    public function __invoke(App $app): void
    {
        io()->title('Removing BugSnag plugin');

        Composer::allowContribRecipes($app);
        Docker::run($app, 'composer remove bugsnag/bugsnag-symfony');
    }
}
