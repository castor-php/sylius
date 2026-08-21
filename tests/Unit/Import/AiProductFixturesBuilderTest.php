<?php

namespace Unit\Import;

use PHPUnit\Framework\TestCase;

use function Castor\Sylius\Import\build_ai_product_fixtures;

final class AiProductFixturesBuilderTest extends TestCase
{
    public function testAiProductFixturesTargetTheImportShopChannel(): void
    {
        $products = [
            [
                'id' => 0,
                'title' => 'Tracteur Massey Ferguson 8500',
                'description' => 'A versatile tractor.',
                'collection_name' => 'Tracteurs',
            ],
        ];

        [$productFixture] = build_ai_product_fixtures(
            $products,
            'tracteurs',
            [0 => 'tracteurs_tracteurs'],
            [0 => 1299999],
        );

        $custom = $productFixture['sylius_fixtures']['suites']['import']['fixtures']['import_products']['options']['custom'];

        self::assertSame('tracteurs_tracteur_massey_ferguson_8500', $custom[0]['code']);
        self::assertSame(['TRACTEURS'], $custom[0]['channels']);
        self::assertSame('tracteurs_tracteurs', $custom[0]['main_taxon']);
        self::assertContains('tracteurs_category', $custom[0]['taxons']);
    }
}
