<?php

declare(strict_types=1);

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsTask;
use Castor\Sylius\App;
use Castor\Sylius\Util\Symfony;
use Castor\Sylius\Util\Yaml;

use function Castor\finder;
use function Castor\fs;
use function Castor\io;

final class B2bTasks
{
    private const string HIDE_CHECKOUT = 'hide_checkout';

    private const string HIDE_PRICES = 'hide_prices';

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
                Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/twig_hooks/**/**.php');

                $hidePrices = io()->choice('Do you want to hide prices for anonymous users?', ['yes', 'no'], 'yes');
                $hideCheckout = io()->choice('Do you want to hide the checkout for anonymous users?', ['yes', 'no'], 'yes');

                if ('yes' === $hidePrices) {
                    self::hidePrices($app);
                }

                if ('yes' === $hideCheckout) {
                    self::hideCheckout($app);
                }

                // Ensure new files on Twig hooks are detected
                Symfony::cacheClear($app);
            },
        ];
    }

    private static function hidePrices(App $app): void
    {
        $configDir = self::configDir(self::HIDE_PRICES);

        foreach (finder()->files()->in($configDir)->files() as $file) {
            fs()->copy($configDir . '/' . $file->getRelativePathname(), $app->directory() . '/config/' . $file->getRelativePathname());
        }

        $templatesDir = self::templatesDir(self::HIDE_PRICES);

        foreach (finder()->files()->in($templatesDir)->files() as $file) {
            fs()->copy($templatesDir . '/' . $file->getRelativePathname(), $app->directory() . '/templates/' . $file->getRelativePathname());
        }

        io()->success('Prices have ben hidden successfully.');
    }

    private static function hideCheckout(App $app): void
    {
        $configDir = self::configDir(self::HIDE_CHECKOUT);

        foreach (finder()->files()->in($configDir)->files() as $file) {
            fs()->copy($configDir . '/' . $file->getRelativePathname(), $app->directory() . '/config/' . $file->getRelativePathname());
        }

        $templatesDir = self::templatesDir(self::HIDE_CHECKOUT);

        foreach (finder()->files()->in($templatesDir)->files() as $file) {
            fs()->copy($templatesDir . '/' . $file->getRelativePathname(), $app->directory() . '/templates/' . $file->getRelativePathname());
        }

        io()->success('Checkout has ben hidden successfully.');
    }

    private static function resourcesDir(string $feature): string
    {
        return \dirname(__DIR__, 2) . '/resources/b2b/' . $feature;
    }

    private static function configDir(string $feature): string
    {
        return self::resourcesDir($feature) . '/config';
    }

    private static function templatesDir(string $feature): string
    {
        return self::resourcesDir($feature) . '/templates';
    }
}
