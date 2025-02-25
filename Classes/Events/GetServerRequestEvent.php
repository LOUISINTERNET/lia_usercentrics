<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Events;

use Psr\Http\Message\ServerRequestInterface;

/**
 * This Event is dispatch if the ServerRequestInterface in the GLOBALS is empty to provide the option to define a custom ServerRequestInterface.
 */
final class GetServerRequestEvent
{
    /**
     * @var ?ServerRequestInterface $request
     */
    protected ?ServerRequestInterface $request;

    /**
     * The Event constructor.
     *
     * @param ?ServerRequestInterface $request
     */
    public function __construct(?ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Set the request.
     *
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Get the request
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }
}
