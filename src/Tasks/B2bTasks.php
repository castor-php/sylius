<?php

declare(strict_types=1);

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsRawTokens;
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
    private const string CUSTOMER_VALIDATION = 'customer_validation';

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
            'function' => static function (#[AsRawTokens] array $features = []) use ($app): void {
                $features = array_values(array_filter(
                    $features,
                    static fn(string $feature): bool => !str_starts_with($feature, '-'),
                ));

                $featureHandlers = [
                    self::HIDE_CHECKOUT => static function () use ($app): void {
                        self::hideCheckout($app);
                    },
                    self::HIDE_PRICES => static function () use ($app): void {
                        self::hidePrices($app);
                    },
                    self::CUSTOMER_VALIDATION => static function () use ($app): void {
                        self::enableCustomerAdminValidation($app);
                    },
                ];

                $availableFeatures = array_keys($featureHandlers);
                sort($availableFeatures);

                if ([] === $features) {
                    $features = io()->choice(
                        'Which B2B features would you like to enable?',
                        $availableFeatures,
                        multiSelect: true,
                    );
                }

                Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/twig_hooks/**/**.php');

                foreach ($features ?? [] as $feature) {
                    if (!isset($featureHandlers[$feature])) {
                        io()->warning(\sprintf('Unknown B2B feature "%s", skipping.', $feature));

                        continue;
                    }

                    $featureHandlers[$feature]();
                }

                Symfony::cacheClear($app);
            },
        ];
    }

    private static function hidePrices(App $app): void
    {
        self::copyResources($app, self::HIDE_PRICES);

        io()->success('Prices have been hidden successfully.');
    }

    private static function hideCheckout(App $app): void
    {
        self::copyResources($app, self::HIDE_CHECKOUT);

        io()->success('Checkout has been hidden successfully.');
    }

    private static function enableCustomerAdminValidation(App $app): void
    {
        Yaml::import($app, 'config/packages/_sylius.yaml', '../sylius/workflows/**/**.php');
        self::copyResources($app, 'customer_validation');

        // Add the workflow on the Customer entity.
        (new PhpFile($app->directory() . '/src/Entity/Customer/Customer.php'))
            ->addImport('Doctrine\ORM\Mapping', 'ORM')
            ->addImport('Doctrine\DBAL\Types\Types')
            ->addImport('Sylius\Resource\Metadata\ApplyStateMachineTransition')
            ->addImport('Sylius\Resource\Metadata\AsResource')
            ->addAttribute(<<<'PHP'
                #[AsResource(
                    section: 'admin',
                    routePrefix: '/%sylius_admin.path_name%',
                    operations: [
                        new ApplyStateMachineTransition(
                            redirectToRoute: 'sylius_admin_customer_update',
                            stateMachineTransition: 'accept',
                        ),
                        new ApplyStateMachineTransition(
                            redirectToRoute: 'sylius_admin_customer_update',
                            stateMachineTransition: 'reject',
                        ),
                    ],
                )]
                PHP)
            ->addClassConstant(<<<'PHP'
                public const string STATE_NEW = 'new';
                public const string STATE_ACCEPTED = 'accepted';
                public const string STATE_REJECTED = 'rejected';
                PHP)
            ->addProperty(<<<'PHP'
                #[ORM\Column(type: Types::STRING, length: 30, options: ['default' => self::STATE_NEW])]
                private string $state = self::STATE_NEW;

                #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
                private ?string $localeCode = null;

                #[ORM\Column(type: Types::STRING, nullable: true)]
                private ?string $registrationChannel = null;
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

                public function getLocaleCode(): ?string
                {
                    return $this->localeCode;
                }

                public function setLocaleCode(?string $localeCode): void
                {
                    $this->localeCode = $localeCode;
                }

                public function getRegistrationChannel(): ?string
                {
                    return $this->registrationChannel;
                }

                public function setRegistrationChannel(?string $registrationChannel): void
                {
                    $this->registrationChannel = $registrationChannel;
                }
                PHP)
            ->save()
        ;

        try {
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
        } catch (\Throwable) {
            io()->info('Your database seems to be already up to date.');
        }

        io()->success('Admin validation for customers has been created successfully.');
    }

    private static function copyResources(App $app, string $feature): void
    {
        $resourcesDir = self::resourcesDir($feature);

        foreach (finder()->files()->in($resourcesDir) as $file) {
            fs()->copy($resourcesDir . '/' . $file->getRelativePathname(), $app->directory() . '/' . $file->getRelativePathname());
        }
    }

    private static function resourcesDir(string $feature): string
    {
        return \dirname(__DIR__, 2) . '/resources/b2b/' . $feature;
    }
}
