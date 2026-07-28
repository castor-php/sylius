<?php

declare(strict_types=1);

namespace Unit\Service;

use Castor\Attribute\AsTask;
use Castor\Docker\Service\PHPService;
use Castor\Sylius\Service\SyliusService;
use PHPUnit\Framework\TestCase;

final class SyliusServiceTest extends TestCase
{
    public function testAddsPhpGdExtension(): void
    {
        $service = new SyliusService();

        $extensions = (new \ReflectionClass(PHPService::class))
            ->getProperty('extensions')
            ->getValue($service)
        ;

        $this->assertContains('gd', $extensions);
    }

    public function testAddsPluginTasks(): void
    {
        $service = new SyliusService();

        $found = false;

        foreach ($service->getTasks() as $task) {
            /** @var AsTask $task */
            $task = $task['task'];

            if ('plugin:add' === $task->name && 'sylius' === $task->namespace) {
                $found = true;

                break;
            }
        }

        $this->assertTrue($found);
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

        $this->assertTrue($found);
    }
}
