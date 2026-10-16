<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Middleware;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;

/**
 * Disables the page cache for requests with the query parameter "uc".
 *
 * The parameter can render a page without Usercentrics (ucQueryParameter). Such a page must neither be served from
 * nor written to the page cache, otherwise visitors without the parameter get the page without consent management.
 * The TypoScript value of ucQueryParameter is not available before the page cache lookup, so every request with the
 * parameter bypasses the cache.
 */
final class BypassCacheMiddleware implements MiddlewareInterface
{
    public const QUERY_PARAMETER = 'uc';

    /**
     * @throws LiaUsercentricsException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!array_key_exists(self::QUERY_PARAMETER, $request->getQueryParams())) {
            return $handler->handle($request);
        }

        $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
        if (!$cacheInstruction instanceof CacheInstruction) {
            throw new LiaUsercentricsException('The request attribute "frontend.cache.instruction" is missing, the page cache cannot be disabled.', 1791905169);
        }
        $cacheInstruction->disableCache('EXT:lia_usercentrics: Request with the query parameter "' . self::QUERY_PARAMETER . '".');

        return $handler->handle($request);
    }
}
