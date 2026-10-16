<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Services\Request;

use LIA\LiaUsercentrics\Events\GetServerRequestEvent;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ServerRequestService
{
    /**
     * Get the ServerRequest.
     */
    public static function getServerRequest(): ?ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request instanceof ServerRequestInterface) {
            return $request;
        }

        $event = new GetServerRequestEvent(null);
        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch($event);

        return $event->getRequest();
    }
}
