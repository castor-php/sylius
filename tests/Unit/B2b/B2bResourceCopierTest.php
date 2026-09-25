<?php

declare(strict_types=1);

namespace Unit\B2b;

use Castor\Container;
use Castor\Sylius\App;
use Castor\Sylius\B2b\B2bResourceCopier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(B2bResourceCopier::class)]
final class B2bResourceCopierTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/b2b_resource_copier_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);

        $container = (new \ReflectionClass(Container::class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(Container::class, 'fs');
        $property->setValue($container, $this->filesystem);
        $property = new \ReflectionProperty(Container::class, 'symfonyStyle');
        $property->setValue($container, new SymfonyStyle(new ArrayInput([]), new BufferedOutput()));
        Container::set($container);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testCopiesFeatureResources(): void
    {
        B2bResourceCopier::copy(new App('test-app', $this->tempDir), 'hide_prices');

        static::assertFileExists($this->tempDir . '/config/sylius/twig_hooks/shop/product/hide_prices.php');
        static::assertFileExists($this->tempDir . '/templates/shop/product/common/price.html.twig');
        static::assertFileExists($this->tempDir . '/templates/shop/product/show/content/info/summary/prices/price.html.twig');
    }
}
