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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UsercentricsServiceIframeTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function iframeProvider(): array
    {
        return [
            'with attributes' => [
                ['width' => '560', 'class' => 'v'],
                '<iframe uc-src="https://y/e" width="560" class="v" data-usercentrics="YouTube Video"></iframe>',
            ],
            'without attributes' => [
                [],
                '<iframe uc-src="https://y/e" data-usercentrics="YouTube Video"></iframe>',
            ],
        ];
    }

    /**
     * @param array<string, string> $tagAttributes
     */
    #[Test]
    #[DataProvider('iframeProvider')]
    public function getModifiedIframeIntegrationKeepsTheTagAttributes(array $tagAttributes, string $expectedIframe): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $readerService = new ReaderService(self::createStub(ConfigurationManagerInterface::class), new TypoScriptService());
        $subject = new UsercentricsService(
            self::createStub(PageRenderer::class),
            new AssetCollector(),
            $readerService,
            self::createStub(EventDispatcher::class),
            new ConsentIntegrationRenderer($readerService, self::createStub(PageRenderer::class), new AssetCollector(), self::createStub(ViewFactoryInterface::class))
        );

        self::assertSame($expectedIframe, $subject->getModifiedIframeIntegration('https://y/e', $tagAttributes, 'YouTube Video'));
    }
}
