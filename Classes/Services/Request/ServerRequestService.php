<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Services\Request;

use LIA\LiaUsercentrics\Events\GetServerRequestEvent;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ServerRequestService
{
    /**
     * Get the ServerRequest.
     */
    public static function getServerRequest(): ?RequestInterface
    {
        $requestInterface = $GLOBALS['TYPO3_REQUEST'];

        if (empty($requestInterface)) {
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
            $event = new GetServerRequestEvent($requestInterface);
            $eventDispatcher->dispatch($event);
            $requestInterface = $event->getRequest();
        }

        return $requestInterface ?? null;
    }
}
