<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Tests\Unit\Services\Integration;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Services\Integration\ConsentIntegrationConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConsentIntegrationConfigurationTest extends UnitTestCase
{
    #[Test]
    public function fromSettingsRejectsTheSmartDataProtectorAtTheSameTime(): void
    {
        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1791905585);
        $this->expectExceptionMessage('The constants "useConsentIntegration" and "useSmartDataProtector" cannot both be enabled');

        ConsentIntegrationConfiguration::fromSettings(['useConsentIntegration' => '1', 'useSmartDataProtector' => '1']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function reloadConstantProvider(): array
    {
        return [
            'reload on opt in' => ['reloadOnOptIn'],
            'reload on opt out' => ['reloadOnOptOut'],
        ];
    }

    #[Test]
    #[DataProvider('reloadConstantProvider')]
    public function fromSettingsRejectsReloadConstants(string $constantName): void
    {
        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1791905586);
        $this->expectExceptionMessage('The constant "' . $constantName . '" is only supported with the Smart Data Protector');

        ConsentIntegrationConfiguration::fromSettings(['useConsentIntegration' => '1', $constantName => 'S1pcEj_jZX']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function overlayProvider(): array
    {
        return [
            'two overlays' => [
                'BJz7qNsdj-7:youtube, S1pcEj_jZX:gMaps',
                '{"overlays":{"BJz7qNsdj-7":"youtube","S1pcEj_jZX":"gMaps"}}',
            ],
            'no overlay' => [
                '',
                '{"overlays":{}}',
            ],
        ];
    }

    #[Test]
    #[DataProvider('overlayProvider')]
    public function toJsonMapsTemplateIdsToTargets(string $customOverlayIntegration, string $expectedJson): void
    {
        $subject = ConsentIntegrationConfiguration::fromSettings([
            'useConsentIntegration' => '1',
            'useSmartDataProtector' => '0',
            'customOverlayIntegration' => $customOverlayIntegration,
        ]);

        self::assertSame($expectedJson, $subject->toJson());
    }

    #[Test]
    public function fromSettingsRejectsAnOverlayEntryWithoutTarget(): void
    {
        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1791905587);
        $this->expectExceptionMessage('The entry "youtube" in the constant "customOverlayIntegration" is not valid');

        ConsentIntegrationConfiguration::fromSettings(['useConsentIntegration' => '1', 'customOverlayIntegration' => 'youtube']);
    }
}
