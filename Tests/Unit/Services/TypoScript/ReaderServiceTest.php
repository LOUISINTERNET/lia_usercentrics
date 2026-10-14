<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Tests\Unit\Services\TypoScript;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Services\TypoScript\ReaderService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReaderServiceTest extends UnitTestCase
{
    #[Test]
    public function getUsercentricsConfigurationReadsThePluginConfiguration(): void
    {
        $subject = $this->createSubject([
            'plugin.' => [
                'tx_liausercentrics.' => [
                    'srcUrl' => 'https://web.cmp.usercentrics.eu/ui/loader.js',
                    'settingsId' => 'example-settings-id',
                    'language' => 'de',
                    'settings.' => ['active' => '1'],
                ],
            ],
        ]);

        self::assertSame(
            [
                'srcUrl' => 'https://web.cmp.usercentrics.eu/ui/loader.js',
                'settingsId' => 'example-settings-id',
                'language' => 'de',
                'settings' => ['active' => '1'],
            ],
            $subject->getUsercentricsConfiguration()
        );
    }

    #[Test]
    public function getUsercentricsConfigurationEvaluatesTheLanguageContentObject(): void
    {
        $languageConfiguration = ['data' => 'siteLanguage:locale:languageCode'];
        $contentObjectRenderer = $this->createMock(ContentObjectRenderer::class);
        $contentObjectRenderer->expects(self::once())
            ->method('cObjGetSingle')
            ->with('TEXT', $languageConfiguration, 'plugin.tx_liausercentrics.language')
            ->willReturn(' de ');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRenderer);

        $subject = $this->createSubject([
            'plugin.' => [
                'tx_liausercentrics.' => [
                    'language' => 'TEXT',
                    'language.' => $languageConfiguration,
                ],
            ],
        ]);

        self::assertSame(['language' => 'de'], $subject->getUsercentricsConfiguration());
    }

    #[Test]
    public function getUsercentricsConfigurationRejectsAMissingPluginConfiguration(): void
    {
        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1740654841);

        $this->createSubject(['plugin.' => ['tx_other.' => ['settings.' => ['active' => '1']]]])->getUsercentricsConfiguration();
    }

    #[Test]
    public function getUsercentricsSettingsReadsThePluginSettings(): void
    {
        $subject = $this->createSubject([
            'plugin.' => [
                'tx_liausercentrics.' => [
                    'settingsId' => 'example-settings-id',
                    'settings.' => ['active' => '1', 'excludedPages' => '12'],
                ],
            ],
        ]);

        self::assertSame(['active' => '1', 'excludedPages' => '12'], $subject->getUsercentricsSettings());
    }

    #[Test]
    public function getUsercentricsSettingsRejectsMissingSettings(): void
    {
        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1741944668);

        $this->createSubject(['plugin.' => ['tx_liausercentrics.' => ['settingsId' => 'example-settings-id']]])->getUsercentricsSettings();
    }

    /**
     * @param array<string, mixed> $fullTypoScript
     */
    private function createSubject(array $fullTypoScript): ReaderService
    {
        $configurationManager = new class ($fullTypoScript) implements ConfigurationManagerInterface {
            /**
             * @param array<string, mixed> $fullTypoScript
             */
            public function __construct(private readonly array $fullTypoScript) {}

            /**
             * @return array<string, mixed>
             */
            public function getConfiguration(string $configurationType, ?string $extensionName = null, ?string $pluginName = null): array
            {
                return $this->fullTypoScript;
            }

            /**
             * @param array<string, mixed> $configuration
             */
            public function setConfiguration(array $configuration = []): void {}

            public function setRequest(ServerRequestInterface $request): void {}
        };

        return new ReaderService($configurationManager, new TypoScriptService());
    }
}
