<?php

declare(strict_types=1);

namespace Castor\Sylius\Util;

use Castor\Sylius\App;

final readonly class Composer
{
    public static function allowContribRecipes(App $app): void
    {
        Docker::run($app, 'composer config extra.symfony.allow-contrib true');
    }
}
