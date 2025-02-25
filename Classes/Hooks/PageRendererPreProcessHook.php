<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Hooks;

use LIA\LiaUsercentrics\Services\Integration\UsercentricsService;
use LIA\LiaUsercentrics\Services\Request\ServerRequestService;
use TYPO3\CMS\Core\Http\ApplicationType;

class PageRendererPreProcessHook
{
    /**
     * Hook constructor
     *
     * @param UsercentricsService $usercentricsService
     */
    public function __construct(private UsercentricsService $usercentricsService) {}

    /**
     * Add the library and configuration to site by typoscript configuration.
     */
    public function addLibrary(): void
    {
        $request = ServerRequestService::getServerRequest();
        if (!empty($request)) {
            if (ApplicationType::fromRequest($request)->isFrontend()
                  && $this->usercentricsService->canBeIntegrated()
            ) {
                $this->usercentricsService->addUsercentricsLibrary();
                $this->usercentricsService->addLibraryConfiguration();
                $this->usercentricsService->addInlineJavaScript();
                $this->usercentricsService->addJavaScriptFile();
            }
        }
    }
}
