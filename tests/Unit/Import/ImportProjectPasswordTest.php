<?php

namespace Unit\Import;

use Castor\Sylius\App;
use Castor\Sylius\Import\ImportContext;
use PHPUnit\Framework\TestCase;

use function Castor\Sylius\Import\build_admin_user_fixture;
use function Castor\Sylius\Import\build_shop_user_fixture;
use function Castor\Sylius\Import\generate_import_password;
use function Castor\Sylius\Import\import_admin_user_password;
use function Castor\Sylius\Import\write_project_config;

final class ImportProjectPasswordTest extends TestCase
{
    private string $root;
    private string $previousCwd;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/castor-import-password-' . uniqid('', true);
        self::assertTrue(mkdir($this->root . '/.castor', 0o775, true));
        $this->previousCwd = getcwd() ?: $this->root;
        chdir($this->root);
        ImportContext::setCurrent(new ImportContext(new App('app', $this->root), 'app'));
    }

    protected function tearDown(): void
    {
        chdir($this->previousCwd);
        $this->removeDirectory($this->root);
    }

    public function testGenerateImportPasswordProducesTwelveAlphanumericCharacters(): void
    {
        $password = generate_import_password();

        self::assertSame(12, \strlen($password));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9]{12}$/', $password);
    }

    public function testBuildAdminUserFixtureUsesSlugAndYamlPassword(): void
    {
        $this->bootstrapProject('cocorico', 'admin-pass-1234', 'shop-pass-5678');

        $fixture = build_admin_user_fixture('cocorico');

        $entry = $fixture['sylius_fixtures']['suites']['import']['fixtures']['import_admin_user']['options']['custom'][0];

        self::assertSame('COCORICO', $entry['channel']);
        self::assertSame('cocorico', $entry['username']);
        self::assertSame('admin-pass-1234', $entry['password']);
        self::assertSame('cocorico@import.local', $entry['email']);
    }

    public function testBuildShopUserFixtureUsesYamlPassword(): void
    {
        $this->bootstrapProject('cocorico', 'admin-pass-1234', 'shop-pass-5678');

        $fixture = build_shop_user_fixture('cocorico');
        $entry = $fixture['sylius_fixtures']['suites']['import']['fixtures']['import_shop_user']['options']['custom'][0];

        self::assertSame('cocorico@shop.local', $entry['email']);
        self::assertSame('shop-pass-5678', $entry['password']);
    }

    public function testImportAdminUserPasswordReadsFromProjectYaml(): void
    {
        $this->bootstrapProject('doudou', 'secret-admin!', 'secret-shop!');

        self::assertSame('secret-admin!', import_admin_user_password('doudou'));
    }

    private function bootstrapProject(string $slug, string $adminPassword, string $shopPassword): void
    {
        $hostDir = $this->root . '/.castor/import/var/' . $slug;
        $media = $hostDir . '/media';
        self::assertTrue(mkdir($media, 0o775, true));
        self::assertNotFalse(file_put_contents($hostDir . '/products.yaml', 'products: []'));
        self::assertNotFalse(file_put_contents($media . '/image_logo.png', 'logo'));

        write_project_config($slug, [
            'slug' => $slug,
            'name' => ucfirst($slug),
            'description' => 'Demo shop',
            'url' => 'https://example.com',
            'mode' => 'existing',
            'admin_password' => $adminPassword,
            'shop_password' => $shopPassword,
            'shop_images' => ['logo' => true, 'header' => false, 'interstice' => false],
        ]);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
