<?php

namespace Unit\Service;

use Castor\Attribute\AsTask;
use Castor\Docker\Service\PHPService;
use Castor\Sylius\Service\SyliusService;
use PHPUnit\Framework\TestCase;

final class SyliusServiceTest extends TestCase
{
    public function testAddsPhpGdAndExifExtensions(): void
    {
        $service = new SyliusService();

        $extensions = (new \ReflectionClass(PHPService::class))
            ->getProperty('extensions')
            ->getValue($service)
        ;

        static::assertContains('gd', $extensions);
        static::assertContains('exif', $extensions);
    }

    public function testAddsPluginTasks(): void
    {
        $service = new SyliusService();

        $found = false;

        foreach ($service->getTasks() as $task) {
            /** @var AsTask $task */
            $task = $task['task'];

            if ('add' === $task->name && 'sylius:plugin' === $task->namespace) {
                $found = true;

                break;
            }
        }

        static::assertTrue($found);
    }

    public function testAddsMenuTasks(): void
    {
        $service = new SyliusService();

        $found = false;

        foreach ($service->getTasks() as $task) {
            /** @var AsTask $task */
            $task = $task['task'];

            if ('remove' === $task->name && 'sylius:menu' === $task->namespace) {
                $found = true;

                break;
            }
        }

        static::assertTrue($found);
    }

    public function testAddsImportTasks(): void
    {
        $service = new SyliusService();
        $expected = [
            ['list', 'sylius:import'],
            ['delete', 'sylius:import'],
            ['fetch', 'sylius:import:existing'],
            ['build', 'sylius:import:ai'],
            ['generate', 'sylius:import:fixtures'],
            ['load', 'sylius:import:fixtures'],
        ];
        $found = [];

        foreach ($service->getTasks() as $task) {
            /** @var AsTask $task */
            $task = $task['task'];

            foreach ($expected as [$name, $namespace]) {
                if ($task->name === $name && $task->namespace === $namespace) {
                    $found["{$namespace}:{$name}"] = true;
                }
            }
        }

        foreach ($expected as [$name, $namespace]) {
            static::assertArrayHasKey("{$namespace}:{$name}", $found);
        }
    }

    public function testAddsAssetsBuildTask(): void
    {
        $service = new SyliusService();
        $found = false;

        foreach ($service->getTasks() as $task) {
            /** @var AsTask $task */
            $task = $task['task'];

            if ('build' === $task->name && 'app:assets' === $task->namespace) {
                $found = true;

                break;
            }
        }

        static::assertTrue($found);
    }

    public function testFixturesLoadCommandIsNonInteractive(): void
    {
        static::assertSame(
            'php bin/console sylius:fixtures:load app -n',
            SyliusService::fixturesLoadCommand(['app']),
        );
        static::assertSame(
            'php bin/console sylius:fixtures:load -n',
            SyliusService::fixturesLoadCommand(),
        );
    }
}
