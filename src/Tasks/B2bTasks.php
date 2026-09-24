<?php

declare(strict_types=1);

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsTask;
use Castor\Sylius\App;
use Castor\Sylius\Util\Symfony;
use Castor\Sylius\Util\Yaml;
use Symfony\Component\Console\Question\Question;

use function Castor\finder;
use function Castor\fs;
use function Castor\io;

final class B2bTasks
{
    public function __construct(
        private readonly string $name,
        private readonly string $directory,
    ) {}

    public function __invoke(): iterable
    {
        $app = new App($this->name, $this->directory);

        yield [
            'task' => new AsTask('enable', 'sylius:b2b', 'Enable b2b features'),
            'function' => static function () use ($app): void {
                $hidePrices = io()->choice('Do you want to hide prices for anonymous users?', ['yes', 'no'], 'yes');

                if ('yes' === $hidePrices) {
                    self::hidePrices($app);
                }

                // Ensure new files on Twig hooks are detected
                Symfony::cacheClear($app);
            },
        ];
    }

    public static function hidePrices(App $app): void
    {
        Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/twig_hooks/**/**.php');

        $resourcesDir = \dirname(__DIR__, 2) . '/resources/b2b';

        fs()->copy(
            $resourcesDir . '/config/sylius/twig_hooks/shop/product/hide_prices.php',
            $app->directory() . '/config/sylius/twig_hooks/shop/product/hide_prices.php',
        );

        $templatesDir = $resourcesDir . '/templates';

        foreach (finder()->files()->in($templatesDir)->files() as $file) {
            fs()->copy($templatesDir . '/' . $file->getRelativePathname(), $app->directory() . '/templates/' . $file->getRelativePathname());
        }
    }
}
