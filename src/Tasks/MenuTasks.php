<?php

declare(strict_types=1);

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Castor\Sylius\App;
use function Castor\fs;
use function Castor\io;

final class MenuTasks
{
    private const string LISTENER_FILE = 'src/Menu/Admin/RemoveMenuItemsListener.php';

    public function __construct(
        private readonly string $name,
        private readonly string $directory,
    ) {
    }

    public function __invoke(): iterable
    {
        $app = new App($this->name, $this->directory);

        yield [
            'task' => new AsTask('remove', 'sylius:menu', 'Remove admin menu items'),
            'function' => function (
                #[AsRawTokens] array $items = [],
            ) use ($app): void {
                if ([] === $items) {
                    $items = io()->choice('Choose menu items', self::getMenuItems(), multiSelect: true);
                }

                // Remove options from the $items
                $items = array_filter($items, static fn (string $item) => !str_starts_with($item, '--'));

                $this->createListener($app, $items);
            },
        ];
    }

    private function createListener(App $app, array $items): void
    {
        fs()->dumpFile($app->directory() . '/' . self::LISTENER_FILE, $this->buildListener($items));
    }

    /**
     * @param string[] $items
     */
    private function buildListener(array $items): string
    {
        $hiddenItems = \sprintf(
            "[\n%s\n    ]",
            implode(
                ",\n",
                array_map(
                    static fn (string $item): string => \sprintf("        '%s'", $item),
                    $items,
                ),
            ),
        );

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Menu\\Admin;

        use Sylius\\Bundle\\UiBundle\\Menu\\Event\\MenuBuilderEvent;
        use Symfony\\Component\\EventDispatcher\\Attribute\\AsEventListener;

        #[AsEventListener(event: 'sylius.menu.admin.main')]
        final class RemoveMenuItemsListener
        {
            private const array REMOVED_MENU_ITEMS = {$hiddenItems};

            public function __invoke(MenuBuilderEvent \$event): void
            {
                \$menu = \$event->getMenu();

                foreach (self::REMOVED_MENU_ITEMS as \$item) {
                    \$parts = explode('/', \$item);

                    if (1 === count(\$parts)) {
                        \$menu->removeChild(\$parts[0]);

                        continue;
                    }

                    \$parent = \$menu->getChild(\$parts[0]);

                    if (null !== \$parent) {
                        \$parent->removeChild(\$parts[1]);
                    }
                }
            }
        }

        PHP;
    }

    /**
     * @return string[]
     */
    private static function getMenuItems(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'catalog' => 'Catalog',
            'catalog/taxons' => 'Catalog/Taxons',
            'catalog/products' => 'Catalog/Products',
            'catalog/inventory' => 'Catalog/Inventory',
            'catalog/attributes' => 'Catalog/Attributes',
            'catalog/options' => 'Catalog/Options',
            'catalog/association_types' => 'Catalog/Association Types',
            'sales' => 'Sales',
            'sales/orders' => 'Sales/Orders',
            'sales/payments' => 'Sales/Payments',
            'sales/shipments' => 'Sales/Shipments',
            'customers' => 'Customers',
            'customers/customers' => 'Customers/Customers',
            'customers/groups' => 'Customers/Groups',
            'marketing' => 'Marketing',
            'marketing/promotions' => 'Marketing/Promotions',
            'marketing/catalog_promotions' => 'Marketing/Catalog Promotions',
            'marketing/product_reviews' => 'Marketing/Product Reviews',
            'configuration' => 'Configuration',
            'configuration/channels' => 'Configuration/Channels',
            'configuration/countries' => 'Configuration/Countries',
            'configuration/zones' => 'Configuration/Zones',
            'configuration/currencies' => 'Configuration/Currencies',
            'configuration/exchange_rates' => 'Configuration/Exchange Rates',
            'configuration/locales' => 'Configuration/Locales',
            'configuration/payment_methods' => 'Configuration/Payment Methods',
            'configuration/shipping_methods' => 'Configuration/Shipping Methods',
            'configuration/shipping_categories' => 'Configuration/Shipping Categories',
            'configuration/tax_categories' => 'Configuration/Tax Categories',
            'configuration/tax_rates' => 'Configuration/Tax Rates',
            'configuration/admin_users' => 'Configuration/Admin Users',
            'official_support' => 'Official Support',
            'official_support/sylius_plus' => 'Official Support/Sylius Plus',
            'official_support/browse_plugins' => 'Official Support/Browse Plugins',
            'official_support/professional_services' => 'Official Support/Professional Services',
            'official_support/find_a_partner' => 'Official Support/Find a Partner',
            'official_support/sylius_certification' => 'Official Support/Sylius Certification',
            'sylius.ui.administration' => 'Administration',
            'sylius.ui.administration/roles' => 'Administration/Roles',
        ];
    }
}
