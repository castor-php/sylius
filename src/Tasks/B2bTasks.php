<?php

declare(strict_types=1);

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsTask;
use Castor\Sylius\App;
use Castor\Sylius\PhpFile;
use Castor\Sylius\Util\Database;
use Castor\Sylius\Util\Filesystem;
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
                $customerAdminValidation = io()->choice('Do you want to add an admin validation for new users?', ['yes', 'no'], 'yes');

                if ('yes' === $hidePrices) {
                    self::hidePrices($app);
                }

                if ('yes' === $hideCheckout) {
                    self::hideCheckout($app);
                }

                if ('yes' === $customerAdminValidation) {
                    self::enableCustomerAdminValidation($app);
                }

                // Ensure new files on Twig hooks are detected
                Symfony::cacheClear($app);
            },
        ];
    }

    private static function hidePrices(App $app): void
    {
        self::copyConfig($app, self::HIDE_PRICES);
        self::copyTemplates($app, self::HIDE_PRICES);

        io()->success('Prices have been hidden successfully.');
    }

    private static function hideCheckout(App $app): void
    {
        self::copyConfig($app, self::HIDE_CHECKOUT);
        self::copyTemplates($app, self::HIDE_CHECKOUT);

        io()->success('Checkout has been hidden successfully.');
    }

    private static function enableCustomerAdminValidation(App $app): void
    {
        Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/workflows/**/**.php');
        self::copyConfig($app, 'customer_validation');

        // Add the workflow on the Customer entity.
        (new PhpFile($app->directory() . '/src/Entity/Customer/Customer.php'))
            ->addImport('Doctrine\ORM\Mapping', 'ORM')
            ->addImport('Doctrine\DBAL\Types\Types')
            ->addClassConstant(<<<'PHP'
                public const string STATE_NEW = 'new';
                public const string STATE_ACCEPTED = 'accepted';
                public const string STATE_REJECTED = 'rejected';
                PHP)
            ->addProperty(<<<'PHP'
                #[ORM\Column(type: Types::STRING, length: 30, options: ['default' => self::STATE_NEW])]
                private string $state = self::STATE_NEW;
                PHP)
            ->addMethod(<<<'PHP'
                public function getState(): string
                {
                    return $this->state;
                }

                public function setState(string $state): void
                {
                    $this->state = $state;
                }
                PHP)
            ->save()
        ;

        Database::diff($app);

        $latestMigration = Filesystem::latestFile($app, 'migrations');

        if (null === $latestMigration) {
            io()->error('No latest migration found.');

            return;
        }

        if (io()->confirm(\sprintf('We have created the "%s" migration file, do you want to execute it now?', $latestMigration))) {
            Database::migrate($app);
        } else {
            io()->caution('Do not forget to sync your database.');
        }
    }

    private static function copyConfig(App $app, string $feature): void
    {
        $configDir = self::configDir($feature);

        foreach (finder()->files()->in($configDir)->files() as $file) {
            fs()->copy($configDir . '/' . $file->getRelativePathname(), $app->directory() . '/config/' . $file->getRelativePathname());
        }
    }

    private static function copyTemplates(App $app, string $feature): void
    {
        $templatesDir = self::templatesDir($feature);

        foreach (finder()->files()->in($templatesDir)->files() as $file) {
            fs()->copy($templatesDir . '/' . $file->getRelativePathname(), $app->directory() . '/templates/' . $file->getRelativePathname());
        }
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
