<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Tests\Unit\Services\Integration;

use LIA\LiaUsercentrics\Services\Integration\ConsentIntegrationRenderer;
use LIA\LiaUsercentrics\Services\Integration\UsercentricsService;
use LIA\LiaUsercentrics\Services\TypoScript\ReaderService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UsercentricsServiceSettingsTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * @return array<string, array{string, int, bool}>
     */
    public static function excludedPagesProvider(): array
    {
        return [
            'listed page' => ['5, 7', 7, true],
            'page not listed' => ['5, 7', 8, false],
            'empty list' => ['', 7, false],
        ];
    }

    #[Test]
    #[DataProvider('excludedPagesProvider')]
    public function isPageExcludedReadsTheExcludedPagesConstant(string $excludedPages, int $pageUid, bool $expected): void
    {
        $subject = $this->createSubject(
            ['settings.' => ['active' => '1', 'excludedPages' => $excludedPages]],
            new ServerRequest('https://example.com/')
        );

        self::assertSame($expected, $subject->isPageExcluded($pageUid));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function queryParameterProvider(): array
    {
        return [
            'matching value' => ['off', true],
            'other value' => ['other', false],
        ];
    }

    #[Test]
    #[DataProvider('queryParameterProvider')]
    public function ignoreIntegrationReadsTheQueryParameterFromThePluginLevel(string $queryValue, bool $expected): void
    {
        $subject = $this->createSubject(
            ['ucQueryParameter' => 'off', 'settings.' => ['active' => '1']],
            (new ServerRequest('https://example.com/?uc=' . $queryValue))->withQueryParams(['uc' => $queryValue])
        );

        self::assertSame($expected, $subject->ignoreIntegration());
    }

    #[Test]
    public function canBeIntegratedReturnsFalseInTheBackendWithoutPageInformation(): void
    {
        $subject = $this->createSubject(
            ['settings.' => ['active' => '1']],
            new ServerRequest('https://example.com/typo3/module/web/info'),
            SystemEnvironmentBuilder::REQUESTTYPE_BE
        );

        self::assertFalse($subject->canBeIntegrated());
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function blockingMechanismProvider(): array
    {
        return [
            'no blocking mechanism' => ['0', '0', false],
            'smart data protector' => ['1', '0', true],
            'consent integration' => ['0', '1', true],
        ];
    }

    #[Test]
    #[DataProvider('blockingMechanismProvider')]
    public function blocksIframesRequiresABlockingMechanism(string $useSmartDataProtector, string $useConsentIntegration, bool $expected): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(7);
        $subject = $this->createSubject(
            ['settings.' => ['active' => '1', 'useSmartDataProtector' => $useSmartDataProtector, 'useConsentIntegration' => $useConsentIntegration]],
            (new ServerRequest('https://example.com/'))->withAttribute('frontend.page.information', $pageInformation)
        );

        self::assertSame($expected, $subject->blocksIframes());
    }

    /**
     * @param array<string, mixed> $pluginTypoScript
     */
    private function createSubject(array $pluginTypoScript, ServerRequestInterface $request, int $applicationType = SystemEnvironmentBuilder::REQUESTTYPE_FE): UsercentricsService
    {
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', $applicationType);

        $configurationManager = new class (['plugin.' => ['tx_liausercentrics.' => $pluginTypoScript]]) implements ConfigurationManagerInterface {
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

        $readerService = new ReaderService($configurationManager, new TypoScriptService());

        return new UsercentricsService(
            self::createStub(PageRenderer::class),
            new AssetCollector(),
            $readerService,
            self::createStub(EventDispatcher::class),
            new ConsentIntegrationRenderer($readerService, self::createStub(PageRenderer::class), new AssetCollector(), self::createStub(ViewFactoryInterface::class))
        );
    }
}
