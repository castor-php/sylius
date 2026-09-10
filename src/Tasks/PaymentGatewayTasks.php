<?php

declare(strict_types=1);

namespace Castor\Sylius\Tasks;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Castor\Sylius\App;
use Castor\Sylius\PaymentGateway\PaymentGateways;
use Castor\Sylius\Plugin\Installer\PluginInstallerInterface;
use Castor\Sylius\Plugin\Remover\PluginRemoverInterface;

use function Castor\io;

final class PaymentGatewayTasks
{
    public function __construct(
        private readonly string $name,
        private readonly string $directory,
    ) {}

    public function __invoke(): iterable
    {
        $app = new App($this->name, $this->directory);

        yield [
            'task' => new AsTask('choose', 'sylius:payment-gateways', 'Choose payment gateways', ['choose-payment-gateways']),
            'function' => static function (#[AsRawTokens] array $paymentGateways = []) use ($app): void {
                $availableGateways = array_intersect(
                    array_map(fn(PluginInstallerInterface $installer) => $installer->name(), PaymentGateways::installers()),
                    array_map(fn(PluginRemoverInterface $remover) => $remover->name(), PaymentGateways::removers()),
                );
                sort($availableGateways);

                $installers = array_map(
                    static fn(callable $installer): callable => static fn() => $installer($app),
                    PaymentGateways::installers(),
                );

                $removers = array_map(
                    static fn(callable $remover): callable => static fn() => $remover($app),
                    PaymentGateways::removers(),
                );

                if ([] === $paymentGateways) {
                    $paymentGateways = io()->choice(
                        'Which payement gateways would you like to use?',
                        $availableGateways,
                        multiSelect: true,
                    );
                }

                if ([] === ($paymentGateways ?? [])) {
                    io()->error('Please choose at least one payment gateway');

                    return;
                }

                $paymentGatewaysToRemove = array_diff($availableGateways, $paymentGateways ?? []);

                foreach ($paymentGateways ?? [] as $paymentGateway) {
                    if (!isset($installers[$paymentGateway])) {
                        io()->error(\sprintf('Unknown payment gateway installer "%s", skipping.', $paymentGateway));

                        return;
                    }
                    $installers[$paymentGateway]();
                }

                foreach ($paymentGatewaysToRemove as $paymentGateway) {
                    if (!isset($removers[$paymentGateway])) {
                        io()->warning(\sprintf('Unknown payment gateway remover "%s", skipping.', $paymentGateway));

                        continue;
                    }
                    $removers[$paymentGateway]();
                }
            },
        ];
    }
}
