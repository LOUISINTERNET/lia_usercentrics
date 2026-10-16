<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Hooks;

use LIA\LiaUsercentrics\Services\Integration\ConsentIntegrationRenderer;
use LIA\LiaUsercentrics\Services\Integration\UsercentricsService;
use LIA\LiaUsercentrics\Services\Request\ServerRequestService;
use TYPO3\CMS\Core\Http\ApplicationType;

class PageRendererPreProcessHook
{
    /**
     * Hook constructor
     *
     * @param UsercentricsService $usercentricsService
     * @param ConsentIntegrationRenderer $consentIntegrationRenderer
     */
    public function __construct(
        private UsercentricsService $usercentricsService,
        private ConsentIntegrationRenderer $consentIntegrationRenderer
    ) {}

    /**
     * Add the library and configuration to site by typoscript configuration.
     */
    public function addLibrary(): void
    {
        $request = ServerRequestService::getServerRequest();
        if ($request === null || !ApplicationType::fromRequest($request)->isFrontend() || !$this->usercentricsService->canBeIntegrated()) {
            return;
        }

        $this->usercentricsService->addUsercentricsLibrary();
        $this->usercentricsService->addLibraryConfiguration();
        $this->usercentricsService->addInlineJavaScript();
        $this->usercentricsService->addJavaScriptFile();

        if ($this->consentIntegrationRenderer->isEnabled()) {
            $this->consentIntegrationRenderer->render($request);
        }
    }
}
