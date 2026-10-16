<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Tests\Unit\Middleware;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Middleware\BypassCacheMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BypassCacheMiddlewareTest extends UnitTestCase
{
    /**
     * @return array<string, array{array<string, string>, bool}>
     */
    public static function queryParameterProvider(): array
    {
        return [
            'with uc parameter' => [['uc' => 'secret'], false],
            'with empty uc parameter' => [['uc' => ''], false],
            'without uc parameter' => [['page' => '2'], true],
        ];
    }

    /**
     * @param array<string, string> $queryParameters
     */
    #[Test]
    #[DataProvider('queryParameterProvider')]
    public function processDisablesThePageCacheForTheUcParameter(array $queryParameters, bool $expectedCachingAllowed): void
    {
        $cacheInstruction = new CacheInstruction();
        $request = (new ServerRequest('https://example.com/'))
            ->withQueryParams($queryParameters)
            ->withAttribute('frontend.cache.instruction', $cacheInstruction);

        (new BypassCacheMiddleware())->process($request, $this->createHandler());

        self::assertSame($expectedCachingAllowed, $cacheInstruction->isCachingAllowed());
    }

    #[Test]
    public function processThrowsWithoutCacheInstruction(): void
    {
        $request = (new ServerRequest('https://example.com/'))->withQueryParams(['uc' => 'secret']);

        $this->expectException(LiaUsercentricsException::class);
        $this->expectExceptionCode(1791905169);

        (new BypassCacheMiddleware())->process($request, $this->createHandler());
    }

    private function createHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }
}
