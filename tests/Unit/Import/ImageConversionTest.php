<?php

namespace Unit\Import;

use PHPUnit\Framework\TestCase;

use function Castor\Sylius\Import\convert_image_to_webp;

require_once \dirname(__DIR__, 3) . '/src/Import/Images/download.php';

final class ImageConversionTest extends TestCase
{
    public function testConvertsJpegToWebp(): void
    {
        if (!\function_exists('imagewebp') || !\function_exists('imagejpeg')) {
            self::markTestSkipped('GD with JPEG and WebP support is required.');
        }

        $directory = sys_get_temp_dir() . '/castor-sylius-webp-' . uniqid('', true);
        self::assertTrue(mkdir($directory, 0o775, true));

        $source = $directory . '/source.jpg';
        $destination = $directory . '/out.webp';

        try {
            $image = imagecreatetruecolor(4, 4);
            self::assertNotFalse($image);
            imagefilledrectangle($image, 0, 0, 3, 3, imagecolorallocate($image, 200, 40, 40));
            self::assertTrue(imagejpeg($image, $source, 80));

            self::assertTrue(convert_image_to_webp($source, $destination));
            self::assertFileExists($destination);
            self::assertGreaterThan(0, filesize($destination));
        } finally {
            foreach ([$source, $destination] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
