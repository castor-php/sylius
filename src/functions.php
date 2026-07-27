<?php

declare (strict_types = 1);

namespace Castor\Sylius;

use Castor\Attribute\AsListener;
use Castor\Event\AfterBootEvent;
use Castor\Exception\FunctionConfigurationException;
use Castor\Sylius\Attribute\AsPluginInstaller;
use Castor\Sylius\Plugin\Installer\BugSnagInstaller;
use Castor\Sylius\Plugin\Installer\CmsInstaller;
use Castor\Sylius\Plugin\Installer\GdprInstaller;
use Castor\Sylius\Plugin\Installer\InvoicingInstaller;
use Castor\Sylius\Plugin\Installer\MediaInstaller;
use Castor\Sylius\Plugin\Installer\PaypalInstaller;
use Castor\Sylius\Plugin\Installer\PluginInstallerDescriptor;
use Castor\Sylius\Plugin\Installer\PluginInstallerInterface;
use Castor\Sylius\Plugin\Installer\RefundInstaller;
use Castor\Sylius\Plugin\Installer\StripeInstaller;
use Castor\Sylius\Plugin\Installer\WishlistInstaller;
use Castor\Sylius\Plugin\Remover\BugSnagRemover;
use Castor\Sylius\Plugin\Remover\CmsRemover;
use Castor\Sylius\Plugin\Remover\GdprRemover;
use Castor\Sylius\Plugin\Remover\InvoicingRemover;
use Castor\Sylius\Plugin\Remover\PaypalRemover;
use Castor\Sylius\Plugin\Remover\StripeRemover;
use Castor\Sylius\Plugin\Remover\WishlistRemover;
use Castor\Sylius\Tasks\PluginTasks;

#[AsListener(AfterBootEvent::class)]
function initialize(AfterBootEvent $afterBootEvent): void
{
    PluginTasks::addInstaller('bugsnag', new BugSnagInstaller());
    PluginTasks::addInstaller('cms', new CmsInstaller());
    PluginTasks::addInstaller('gdpr', new GdprInstaller());
    PluginTasks::addInstaller('invoicing', new InvoicingInstaller());
    PluginTasks::addInstaller('media', new MediaInstaller());
    PluginTasks::addInstaller('paypal', new PaypalInstaller());
    PluginTasks::addInstaller('refund', new RefundInstaller());
    PluginTasks::addInstaller('stripe', new StripeInstaller());
    PluginTasks::addInstaller('wishlist', new WishlistInstaller());

    PluginTasks::addRemover('bugsnag', new BugSnagRemover());
    PluginTasks::addRemover('cms', new CmsRemover());
    PluginTasks::addRemover('gdpr', new GdprRemover());
    PluginTasks::addRemover('invoicing', new InvoicingRemover());
    PluginTasks::addRemover('paypal', new PaypalRemover());
    PluginTasks::addRemover('stripe', new StripeRemover());
    PluginTasks::addRemover('wishlist', new WishlistRemover());

    $currentFunctions = get_defined_functions()['user'];
    $currentClasses = get_declared_classes();

    foreach ($currentFunctions as $function) {
        $reflectionFunction = new \ReflectionFunction($function);
        $descriptor = resolve_plugin_installer($reflectionFunction);

        if (null === $descriptor) {
            continue;
        }

        $name = $descriptor->attribute->name;
        $installer = new Plugin\Installer\PluginInstaller($descriptor->installer->getClosure());
        PluginTasks::addInstaller($name, $installer);
    }

    foreach ($currentClasses as $class) {
        $reflectionClass = new \ReflectionClass($class);
        $descriptor = resolve_plugin_installer($reflectionClass);

        if (null === $descriptor) {
            continue;
        }

        PluginTasks::addInstaller($descriptor->attribute->name, $descriptor->installer);
    }
}

function resolve_plugin_installer(\ReflectionFunction|\ReflectionClass $reflection): ?PluginInstallerDescriptor
{
    $attributes = $reflection->getAttributes(AsPluginInstaller::class, \ReflectionAttribute::IS_INSTANCEOF);
    if (!\count($attributes)) {
        return null;
    }

    try {
        /** @var AsPluginInstaller $installerAttribute */
        $installerAttribute = $attributes[0]->newInstance();
    } catch (\Throwable $e) {
        throw new FunctionConfigurationException(\sprintf('Could not instantiate the attribute "%s".', AsPluginInstaller::class), $reflection, $e);
    }

    if ($reflection instanceof \ReflectionFunction) {
        return new PluginInstallerDescriptor($installerAttribute, $reflection);
    }

    if (!is_a($reflection->name, PluginInstallerInterface::class, true)) {
        throw new FunctionConfigurationException(\sprintf('"%s should be an instance of %s".', $reflection->name, PluginInstallerInterface::class), $reflection);
    }

    try {
        $instance = $reflection->newInstance();
    } catch (\Throwable $e) {
        throw new FunctionConfigurationException(\sprintf('Could not instantiate the class "%s".', $reflection->name), $reflection, $e);
    }

    return new PluginInstallerDescriptor($installerAttribute, $instance);
}
