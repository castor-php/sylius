<?php

declare (strict_types=1);

namespace Castor\Sylius;

use Castor\Attribute\AsListener;
use Castor\Event\AfterBootEvent;
use Castor\Exception\FunctionConfigurationException;
use Castor\Sylius\Attribute\AsPluginInstaller;
use Castor\Sylius\Attribute\AsPluginRemover;
use Castor\Sylius\Plugin\Installer\BugSnagInstaller;
use Castor\Sylius\Plugin\Installer\CmsInstaller;
use Castor\Sylius\Plugin\Installer\GdprInstaller;
use Castor\Sylius\Plugin\Installer\InvoicingInstaller;
use Castor\Sylius\Plugin\Installer\MediaInstaller;
use Castor\Sylius\Plugin\Installer\PaypalInstaller;
use Castor\Sylius\Plugin\Installer\PluginInstaller;
use Castor\Sylius\Plugin\Installer\PluginInstallerDescriptor;
use Castor\Sylius\Plugin\Installer\PluginInstallerInterface;
use Castor\Sylius\Plugin\Installer\RefundInstaller;
use Castor\Sylius\Plugin\Installer\StripeInstaller;
use Castor\Sylius\Plugin\Installer\WishlistInstaller;
use Castor\Sylius\Plugin\Remover\BugSnagRemover;
use Castor\Sylius\Plugin\Remover\CmsRemover;
use Castor\Sylius\Plugin\Remover\GdprRemover;
use Castor\Sylius\Plugin\Remover\InvoicingRemover;
use Castor\Sylius\Plugin\Remover\MollieRemover;
use Castor\Sylius\Plugin\Remover\PaypalRemover;
use Castor\Sylius\Plugin\Remover\PluginRemover;
use Castor\Sylius\Plugin\Remover\PluginRemoverDescriptor;
use Castor\Sylius\Plugin\Remover\StripeRemover;
use Castor\Sylius\Plugin\Remover\WishlistRemover;
use Castor\Sylius\Tasks\PluginTasks;

#[AsListener(AfterBootEvent::class)]
function initialize(AfterBootEvent $afterBootEvent): void
{
    PluginTasks::addInstaller(new BugSnagInstaller());
    PluginTasks::addInstaller(new CmsInstaller());
    PluginTasks::addInstaller(new GdprInstaller());
    PluginTasks::addInstaller(new InvoicingInstaller());
    PluginTasks::addInstaller(new MediaInstaller());
    PluginTasks::addInstaller(new PaypalInstaller());
    PluginTasks::addInstaller(new RefundInstaller());
    PluginTasks::addInstaller(new StripeInstaller());
    PluginTasks::addInstaller(new WishlistInstaller());

    PluginTasks::addRemover(new BugSnagRemover());
    PluginTasks::addRemover(new CmsRemover());
    PluginTasks::addRemover(new GdprRemover());
    PluginTasks::addRemover(new InvoicingRemover());
    PluginTasks::addRemover(new MollieRemover());
    PluginTasks::addRemover(new PaypalRemover());
    PluginTasks::addRemover(new StripeRemover());
    PluginTasks::addRemover(new WishlistRemover());

    $currentFunctions = get_defined_functions()['user'];
    $currentClasses = get_declared_classes();

    foreach ($currentFunctions as $function) {
        $reflectionFunction = new \ReflectionFunction($function);
        $descriptor = resolve_plugin_installer($reflectionFunction);

        if (null !== $descriptor) {
            $installer = new Plugin\Installer\PluginInstaller($descriptor->attribute->name, $descriptor->installer->getClosure());
            PluginTasks::addInstaller($installer);
        }

        $descriptor = resolve_plugin_remover($reflectionFunction);

        if (null === $descriptor) {
            continue;
        }

        $remover = new Plugin\Remover\PluginRemover($descriptor->attribute->name, $descriptor->remover->getClosure());
        PluginTasks::addRemover($remover);
    }

    foreach ($currentClasses as $class) {
        $reflectionClass = new \ReflectionClass($class);
        $descriptor = resolve_plugin_installer($reflectionClass);

        if (null !== $descriptor) {
            PluginTasks::addInstaller($descriptor->installer);
        }

        $descriptor = resolve_plugin_remover($reflectionClass);

        if (null === $descriptor) {
            continue;
        }

        PluginTasks::addRemover($descriptor->remover);
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

    try {
        $instance = $reflection->newInstance();
    } catch (\Throwable $e) {
        throw new FunctionConfigurationException(\sprintf('Could not instantiate the class "%s".', $reflection->name), $reflection, $e);
    }

    if (!\is_callable($instance)) {
        throw new FunctionConfigurationException(\sprintf('"%s" is not callable.', $reflection->name), $reflection, $instance);
    }

    return new PluginInstallerDescriptor($installerAttribute, new PluginInstaller($installerAttribute->name, fn() => $instance()));
}

function resolve_plugin_remover(\ReflectionFunction|\ReflectionClass $reflection): ?PluginRemoverDescriptor
{
    $attributes = $reflection->getAttributes(AsPluginRemover::class, \ReflectionAttribute::IS_INSTANCEOF);
    if (!\count($attributes)) {
        return null;
    }

    try {
        /** @var AsPluginRemover $removerAttribute */
        $removerAttribute = $attributes[0]->newInstance();
    } catch (\Throwable $e) {
        throw new FunctionConfigurationException(\sprintf('Could not instantiate the attribute "%s".', AsPluginRemover::class), $reflection, $e);
    }

    if ($reflection instanceof \ReflectionFunction) {
        return new PluginRemoverDescriptor($removerAttribute, $reflection);
    }

    try {
        $instance = $reflection->newInstance();
    } catch (\Throwable $e) {
        throw new FunctionConfigurationException(\sprintf('Could not instantiate the class "%s".', $reflection->name), $reflection, $e);
    }

    if (!\is_callable($instance)) {
        throw new FunctionConfigurationException(\sprintf('"%s" is not callable.', $reflection->name), $reflection, $instance);
    }

    return new PluginRemoverDescriptor($removerAttribute, new PluginRemover($removerAttribute->name, fn() => $instance()));
}
