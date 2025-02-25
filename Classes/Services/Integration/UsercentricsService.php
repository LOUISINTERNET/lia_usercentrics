<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Services\Integration;

use LIA\LiaUsercentrics\Events\AddCustomLibrarySettingsEvent;
use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Services\Request\ServerRequestService;
use LIA\LiaUsercentrics\Services\TypoScript\ReaderService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This class provide function for the integration of the Usercentrics library and the
 * configured javascript services.
 */
class UsercentricsService
{
    /**
     * @var PageRenderer $pageRenderer
     */
    protected PageRenderer $pageRenderer;

    /**
     * @var AssetCollector $assetCollector
     */
    protected AssetCollector $assetCollector;

    /**
     * @var ReaderService $typoscriptReader
     */
    protected ReaderService $typoscriptReader;

    /**
     * Service constructor
     *
     * @param PageRenderer $pageRenderer
     * @param AssetCollector $assetCollector
     * @param ReaderService $typoscriptReader
     * @param EventDispatcher $dispatcher
     */
    public function __construct(
        PageRenderer $pageRenderer,
        AssetCollector $assetCollector,
        ReaderService $typoscriptReader,
        private EventDispatcher $dispatcher
    ) {
        $request = ServerRequestService::getServerRequest();
        if (!empty($request)) {
            if (ApplicationType::fromRequest($request)->isFrontend()) {
                $this->pageRenderer = $pageRenderer;
                $this->assetCollector = $assetCollector;
                $this->typoscriptReader = $typoscriptReader;
            }
        }
    }

    /**
     * Check if the Usercentrics library can be integrated on the current page.
     */
    public function canBeIntegrated(): bool
    {
        $request = ServerRequestService::getServerRequest();
        if (empty($request)) {
            return false;
        }

        $currentPageUid = $request->getAttribute('frontend.page.information')->getId();
        return ApplicationType::fromRequest($request)->isFrontend()
            && $this->isActive() && !$this->ignoreIntegration() && !$this->isPageExcluded($currentPageUid);
    }

    /**
     * Check if the integration is activated.
     */
    public function isActive(): bool
    {
        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();

        if (!in_array($usercentricsSettings['active'], ['0', '1'])) {
            throw new LiaUsercentricsException('The value of the constant active is not valid. It can be only 0 or 1.', 1740575107);
        }

        return (bool)$usercentricsSettings['active'];
    }

    /**
     * Check if the integration is excluded for the current page.
     *
     * @param int $currentPageUid
     */
    public function isPageExcluded(int $currentPageUid): bool
    {
        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();
        if (empty($usercentricsSettings['excludeOnPages'])) {
            return false;
        }

        $pageUidList = array_filter(GeneralUtility::trimExplode(',', $usercentricsSettings['excludeOnPages']));
        if (empty($pageUidList)) {
            return false;
        }

        return in_array($currentPageUid, $pageUidList);
    }

    /**
     * Check if the integration should be ignored by set get parameter.
     */
    public function ignoreIntegration(): bool
    {
        $request = ServerRequestService::getServerRequest();
        if (empty($request)) {
            return true;
        }

        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();
        if (empty($usercentricsSettings)) {
            throw new LiaUsercentricsException('The Usercentrics settings are not set.', 1741944668);
        }

        if (!empty($request->getQueryParams()['uc']) && !empty($usercentricsSettings['ucQueryParameter'])) {
            return $request->getQueryParams()['uc'] === $usercentricsSettings['ucQueryParameter'];
        }

        return false;
    }

    /**
     * Add the Usercentrics library to the page header data.
     *
     * @throws LiaUsercentricsException
     */
    public function addUsercentricsLibrary(): void
    {
        $usercentricsConfig = $this->typoscriptReader->getUsercentricsConfiguration();

        $settingsId = $usercentricsConfig['settingsId'];
        $language = $usercentricsConfig['language'];
        $srcUrl = $usercentricsConfig['srcUrl'];
        $useSDP = $usercentricsConfig['settings']['useSmartDataProtector'] ?? false;
        $async = (bool) $usercentricsConfig['settings']['async'] ?? false;

        if (empty($srcUrl) || empty($language) || empty($settingsId)) {
            throw new LiaUsercentricsException('Your Usercentrics configuration is not valid. Please make sure that the srcUrl, settingsId and language constants are set.', 1740655898);
        }

        if (!empty($srcUrl) && !empty($language) && !empty($settingsId)) {
            $asyncAttr = '';
            if ($async) {
                $asyncAttr = 'async';
            }
            $this->pageRenderer->addHeaderData(
                '<script id="usercentrics-cmp" src="' . $srcUrl . '" data-settings-id="' . $settingsId . '" data-language="' . $language . '" ' . $asyncAttr . '></script>'
            );
            if ($useSDP) {
                $this->pageRenderer->addHeaderData(
                    '<script type="application/javascript" id="usercentrics-smart-data-protector" src="https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js"></script>'
                );
            }
        }
    }

    /**
     * Add the configured inline javascript to the asset collector.
     *
     * @throws LiaUsercentricsException
     */
    public function addInlineJavaScript(): void
    {
        $usercentricsConfig = $this->typoscriptReader->getUsercentricsConfiguration();

        if (!empty($usercentricsConfig['jsInline'])) {
            foreach ($usercentricsConfig['jsInline'] as $index => $inLineItem) {
                if (!$this->isJsConfigurationValid($inLineItem)) {
                    throw new LiaUsercentricsException('The inline javascript configuration for the index "' . $index . '" is not valid.', 1740577586);
                }

                [$identifier, $attributes, $options] = $this->getTagData($inLineItem);
                $this->assetCollector->addInlineJavaScript($identifier, $inLineItem['value'], $attributes, $options);
            }
        }
    }

    /**
     * Add the configured javascript file or libraries to the asset collector.
     *
     * @throws LiaUsercentricsException
     */
    public function addJavaScriptFile(): void
    {
        $usercentricsConfig = $this->typoscriptReader->getUsercentricsConfiguration();

        if (!empty($usercentricsConfig['jsFiles'])) {
            foreach ($usercentricsConfig['jsFiles'] as $index => $file) {
                if (!$this->isJsConfigurationValid($file, true)) {
                    throw new LiaUsercentricsException("The JavaScript file configuration in the index '" . $index . "' is not valid", 1740636489);
                }

                [$identifier, $attributes, $options] = $this->getTagData($file);
                $this->assetCollector->addJavaScript($identifier, $file['file'], $attributes, $options);
            }
        }
    }

    /**
     * Add additional Usercentrics configurations like a custom overlay configuration or configuration for the smart data protector.
     */
    public function addLibraryConfiguration(): void
    {
        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();

        if (!empty($usercentricsSettings['whitelistedSDPServices'])) {
            $this->addBlockOnlyInlineScript($usercentricsSettings['whitelistedSDPServices']);
        }

        if (!empty($usercentricsSettings['customOverlayIntegration'])) {
            $this->customOverlayIntegration($usercentricsSettings['customOverlayIntegration']);
        }

        if (!empty($usercentricsSettings['reloadOnOptIn'])) {
            $this->addReloadOnOptInOrOutConfiguration($usercentricsSettings['reloadOnOptIn']);
        }

        if (!empty($usercentricsSettings['reloadOnOptOut'])) {
            $this->addReloadOnOptInOrOutConfiguration($usercentricsSettings['reloadOnOptOut'], 'reloadOnOptOut');
        }

        // Provide opportunity to add custom settings.
        $event = new AddCustomLibrarySettingsEvent(
            $this->pageRenderer,
            $this->assetCollector,
            $usercentricsSettings
        );
        $this->dispatcher->dispatch($event);
        $this->pageRenderer = $event->getPageRenderer();
        $this->assetCollector = $event->getAssetCollector();
    }

    /**
     * Modify the iframe tag string to block the service.
     *
     * @param string $src
     * @param array $tagAttributes
     * @param string $dataProcessingService
     */
    public function getModifiedIframeIntegration(string $src, array $tagAttributes, string $dataProcessingService): string
    {
        return sprintf(
            '<iframe uc-src="%s"%s data-usercentrics="%s"></iframe>',
            htmlspecialchars($src, ENT_QUOTES | ENT_HTML5),
            empty($attributes) ? '' : ' ' . GeneralUtility::implodeAttributes($tagAttributes),
            $dataProcessingService
        );
    }

    /**
     * Check the typoscript configuration of the javascript files or inline configuration.
     * It does not check the content of the configuration.
     *
     * @param array $jsConf
     * @param bool $isFile
     */
    protected function isJsConfigurationValid(array $jsConf, bool $isFile = false): bool
    {
        $dataProcessingService = $jsConf['dataProcessingService'];

        if ($isFile) {
            $file = $jsConf['file'];
            if (
                !empty($file)
                && str_starts_with('EXT:', $file)
                && file_exists(GeneralUtility::getFileAbsFileName($file))
                && isset($dataProcessingService)
                && is_string($dataProcessingService)
            ) {
                return true;
            }
            if (!empty($file) && filter_var($file, FILTER_VALIDATE_URL) && isset($dataProcessingService) && is_string($dataProcessingService)) {
                return true;
            }

            return isset($file) && is_string($file) && isset($dataProcessingService) && is_string($dataProcessingService);
        }

        return !empty($jsConf['value']) && isset($dataProcessingService) && is_string($dataProcessingService);
    }

    /**
     * Convert the priority value to an boolean.
     *
     * @param array $options
     */
    protected function convertPriorityToBoolean(array $options): array
    {
        if (!empty($options['priority'])) {
            $options['priority'] = (bool)$options['priority'];
        }

        return $options;
    }

    /**
     * Limits the Smart data protector to configured services.
     *
     * @param string $whitelistedServices
     */
    protected function addBlockOnlyInlineScript(string $whitelistedServices): void
    {
        $blockOnlyServices = json_encode(GeneralUtility::trimExplode(',', $whitelistedServices, true));
        $this->pageRenderer->addHeaderData('<script type="text/javascript" id="blockOnly">uc.blockOnly(' . $blockOnlyServices . ');</script>');
    }

    /**
     * Add configuration to add Usercentrics to custom services integrations.
     *
     * @param string $overlayIntegration
     */
    protected function customOverlayIntegration(string $overlayIntegration): void
    {
        $customServiceOverlay = $this->buildCustomOverlayJson(
            GeneralUtility::trimExplode(',', $overlayIntegration, true)
        );

        $this->pageRenderer->addHeaderData('<script type="text/javascript" id="blockElements">uc.blockElements(' . $customServiceOverlay . ');</script>');
    }

    /**
     * Add a configuration to reload the page on opt in or on opt out.
     *
     * @param string $services
     * @param string $functionName
     */
    protected function addReloadOnOptInOrOutConfiguration(string $services, string $functionName = 'reloadOnOptIn'): void
    {
        $serviceIds = GeneralUtility::trimExplode(',', $services, true);
        $functionCallList = [];

        foreach ($serviceIds as $id) {
            $functionCallList[] = 'uc.' . $functionName . '(\'' . $id . '\')';
        }

        $this->pageRenderer->addHeaderData("<script type='text/javascript' id='uc-" . $functionName . "'>" . implode(';', $functionCallList) . "</script>");
    }

    /**
     * Get the needed data for the integration of the configured javascript data.
     *
     * @param array $jsConf
     */
    private function getTagData(array $jsConf): array
    {
        $dataProcessingService = $jsConf['dataProcessingService'];
        $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
        $attributes = [
            'type' => 'text/plain',
            'data-usercentrics' => $dataProcessingService,
        ];
        $options = $this->convertPriorityToBoolean($jsConf['options'] ?? []);

        return [$identifier, $attributes, $options];
    }

    /**
     * Generate a json for the Usercentrics custom overlay integration by given typoscript configuration.
     *
     * @param array $customServicesMap
     */
    private function buildCustomOverlayJson(array $customServicesMap): string
    {
        $return = [];
        foreach ($customServicesMap as $map) {
            if (strpos($map, ':') > 0) {
                $serviceMap = GeneralUtility::trimExplode(':', $map);
                $return[$serviceMap[0]] = '[data-uc="' . $serviceMap[1] . '"]';
            }
        }

        return json_encode($return);
    }
}
