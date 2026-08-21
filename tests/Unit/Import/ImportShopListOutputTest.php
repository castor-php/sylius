<?php

namespace Unit\Import;

use PHPUnit\Framework\TestCase;

use function Castor\Sylius\Import\import_shop_list_table;
use function Castor\Sylius\Import\mode_from_import_sources;

require_once \dirname(__DIR__, 3) . '/src/Import/project.php';

final class ImportShopListOutputTest extends TestCase
{
    /**
     * @return list<array{
     *     slug: string,
     *     name: string,
     *     mode: string|null,
     *     hasYaml: bool,
     *     hasFixtures: bool,
     *     channelCode: string,
     *     shopUrl: string,
     *     adminUrl: string,
     *     adminPassword: string|null,
     *     shopPassword: string|null,
     *     shopUserEmail: string,
     * }>
     */
    private function shops(): array
    {
        return [
            [
                'slug' => 'cocorico',
                'name' => 'Cocorico',
                'mode' => 'existing',
                'hasYaml' => true,
                'hasFixtures' => false,
                'channelCode' => 'COCORICO',
                'shopUrl' => 'https://cocorico.prevente.test',
                'adminUrl' => 'https://cocorico.prevente.test/admin',
                'adminPassword' => 'admin-pass-12',
                'shopPassword' => 'shop-pass-12',
                'shopUserEmail' => 'cocorico@shop.local',
            ],
            [
                'slug' => 'tracteurs-and-co',
                'name' => 'Tracteurs and co',
                'mode' => null,
                'hasYaml' => false,
                'hasFixtures' => true,
                'channelCode' => 'TRACTEURS_AND_CO',
                'shopUrl' => 'https://tracteurs-and-co.prevente.test',
                'adminUrl' => 'https://tracteurs-and-co.prevente.test/admin',
                'adminPassword' => null,
                'shopPassword' => null,
                'shopUserEmail' => 'tracteurs-and-co@shop.local',
            ],
        ];
    }

    public function testTableRowsMapShopFieldsForTheCli(): void
    {
        $table = import_shop_list_table($this->shops());

        self::assertSame(
            ['Name', 'Slug', 'Mode', 'YAML', 'Fixtures', 'Channel'],
            $table['headers'],
        );
        self::assertSame(
            [
                ['Cocorico', 'cocorico', 'existing', 'yes', 'no', 'COCORICO'],
                ['Tracteurs and co', 'tracteurs-and-co', '—', 'no', 'yes', 'TRACTEURS_AND_CO'],
            ],
            $table['rows'],
        );
    }

    public function testTableIsEmptyWhenThereAreNoShops(): void
    {
        $table = import_shop_list_table([]);

        self::assertSame([], $table['rows']);
    }

    public function testModePrefersProjectConfigOverProductsYaml(): void
    {
        self::assertSame('ai', mode_from_import_sources(
            ['mode' => 'ai'],
            ['mode' => 'existing'],
        ));
    }

    public function testModeFallsBackToProductsYaml(): void
    {
        self::assertSame('existing', mode_from_import_sources(
            null,
            ['mode' => 'existing', 'name' => 'Cocorico'],
        ));
    }

    public function testBlankModeIsIgnored(): void
    {
        self::assertSame('ai', mode_from_import_sources(
            ['mode' => '  '],
            ['mode' => 'ai'],
        ));
        self::assertNull(mode_from_import_sources(['mode' => ''], null));
        self::assertNull(mode_from_import_sources(null, null));
    }
}
