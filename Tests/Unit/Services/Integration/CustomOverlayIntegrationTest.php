<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Tests\Unit\Services\Integration;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Services\Integration\CustomOverlayIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CustomOverlayIntegrationTest extends UnitTestCase
{
    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function validProvider(): array
    {
        return [
            'two entries' => [
                'BJz7qNsdj-7:youtube, S1pcEj_jZX:gMaps',
                ['BJz7qNsdj-7' => 'youtube', 'S1pcEj_jZX' => 'gMaps'],
            ],
            'spaces around the colon' => [
                'S1pcEj_jZX : gMaps',
                ['S1pcEj_jZX' => 'gMaps'],
            ],
            'empty constant' => [
                '',
                [],
            ],
        ];
    }

    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('validProvider')]
    public function parseMapsTemplateIdsToTargets(string $customOverlayIntegration, array $expected): void
    {
        self::assertSame($expected, CustomOverlayIntegration::parse($customOverlayIntegration));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'without target' => ['youtube', 'youtube'],
            'without template id' => [':youtube', ':youtube'],
            'two colons' => ['S1pcEj_jZX:gMaps:x', 'S1pcEj_jZX:gMaps:x'],
            'quote in target' => ['S1pcEj_jZX:g"Maps', 'S1pcEj_jZX:g"Maps'],
            'invalid second entry' => ['S1pcEj_jZX:gMaps, youtube', 'youtube'],
        ];
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function parseRejectsAnInvalidEntry(string $customOverlayIntegration, string $invalidEntry): void
    {
        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1791905587);
        $this->expectExceptionMessage('The entry "' . $invalidEntry . '" in the constant "customOverlayIntegration" is not valid');

        CustomOverlayIntegration::parse($customOverlayIntegration);
    }
}
