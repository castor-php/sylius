<?php

namespace Unit\Import;

use PHPUnit\Framework\TestCase;

use function Castor\Sylius\Import\import_ai_platform_classes_available;
use function Castor\Sylius\Import\missing_import_ai_packages_message;

final class ImportVendorTest extends TestCase
{
    public function testMissingAiPackagesMessagePointsToCastorComposerUpdate(): void
    {
        self::assertStringContainsString(
            'castor composer update',
            missing_import_ai_packages_message(),
        );
    }

    public function testAiPlatformClassesAreDetectedWhenAutoloaded(): void
    {
        $available = import_ai_platform_classes_available();

        if (class_exists(\Symfony\AI\Platform\Bridge\Ollama\Factory::class)) {
            self::assertTrue($available);

            return;
        }

        self::assertFalse($available);
    }
}
