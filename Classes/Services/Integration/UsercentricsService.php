<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Services\Integration;

use LIA\LiaUsercentrics\Events\AddCustomLibrarySettingsEvent;
use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Middleware\BypassCacheMiddleware;
use LIA\LiaUsercentrics\Services\Request\ServerRequestService;
use LIA\LiaUsercentrics\Services\TypoScript\ReaderService;
use LIA\LiaUsercentrics\Services\TypoScript\TypoScriptValue;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * This class provide function for the integration of the Usercentrics library and the
 * configured javascript services.
 */
class UsercentricsService
{
    /**
     * Service constructor
     *
     * @param PageRenderer $pageRenderer
     * @param AssetCollector $assetCollector
     * @param ReaderService $typoscriptReader
     * @param EventDispatcher $dispatcher
     * @param ConsentIntegrationRenderer $consentIntegrationRenderer
     */
    public function __construct(
        protected PageRenderer $pageRenderer,
        protected AssetCollector $assetCollector,
        protected ReaderService $typoscriptReader,
        private EventDispatcher $dispatcher,
        private ConsentIntegrationRenderer $consentIntegrationRenderer
    ) {}

    /**
     * Check if the Usercentrics library can be integrated on the current page.
     */
    public function canBeIntegrated(): bool
    {
        $request = ServerRequestService::getServerRequest();
        if ($request === null || !ApplicationType::fromRequest($request)->isFrontend()) {
            return false;
        }

        $pageInformation = $request->getAttribute('frontend.page.information');
        if (!$pageInformation instanceof PageInformation) {
            throw new LiaUsercentricsException('The request attribute "frontend.page.information" is missing, the current page cannot be determined.', 1791905168);
        }

        return $this->isActive() && !$this->ignoreIntegration() && !$this->isPageExcluded($pageInformation->getId());
    }

    /**
     * Check if iframes have to be rendered with uc-src. Only the Smart Data Protector or the consent integration
     * turn uc-src into src, without one of them the iframes would never load.
     *
     * @throws LiaUsercentricsException
     */
    public function blocksIframes(): bool
    {
        if (!$this->canBeIntegrated()) {
            return false;
        }

        return !empty($this->typoscriptReader->getUsercentricsSettings()['useSmartDataProtector'])
            || $this->consentIntegrationRenderer->isEnabled();
    }

    /**
     * Check if the integration is activated.
     */
    public function isActive(): bool
    {
        $request = ServerRequestService::getServerRequest();
        if (empty($request) || !ApplicationType::fromRequest($request)->isFrontend()) {
            return false;
        }

        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();

        $active = TypoScriptValue::getString($usercentricsSettings, 'active');
        if (!in_array($active, ['0', '1'], true)) {
            throw new LiaUsercentricsException('The value of the constant active is not valid. It can be only 0 or 1.', 1740575107);
        }

        return $active === '1';
    }

    /**
     * Check if the integration is excluded for the current page.
     *
     * @param int $currentPageUid
     */
    public function isPageExcluded(int $currentPageUid): bool
    {
        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();
        $pageUidList = GeneralUtility::intExplode(',', TypoScriptValue::getString($usercentricsSettings, 'excludedPages'), true);

        return in_array($currentPageUid, $pageUidList, true);
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

        $queryParameter = TypoScriptValue::getString($this->typoscriptReader->getUsercentricsConfiguration(), 'ucQueryParameter');

        return $queryParameter !== '' && ($request->getQueryParams()[BypassCacheMiddleware::QUERY_PARAMETER] ?? null) === $queryParameter;
    }

    /**
     * Add the Usercentrics library to the page header data.
     *
     * @throws LiaUsercentricsException
     */
    public function addUsercentricsLibrary(): void
    {
        $usercentricsConfig = $this->typoscriptReader->getUsercentricsConfiguration();

        $settings = TypoScriptValue::getArray($usercentricsConfig, 'settings');

        $settingsId = TypoScriptValue::getString($usercentricsConfig, 'settingsId');
        $language = TypoScriptValue::getString($usercentricsConfig, 'language');
        $srcUrl = TypoScriptValue::getString($usercentricsConfig, 'srcUrl');
        $useSDP = !empty($settings['useSmartDataProtector']);
        $async = !empty($settings['async']);
        $tcfEnabled = !empty($settings['tcfEnabled']);

        if (empty($srcUrl) || empty($language) || empty($settingsId)) {
            throw new LiaUsercentricsException('Your Usercentrics configuration is not valid. Please make sure that the srcUrl, settingsId and language constants are set.', 1740655898);
        }

        $this->pageRenderer->addHeaderData(
            '<script id="usercentrics-cmp" src="' . $srcUrl . '" data-settings-id="' . $settingsId . '" data-language="' . $language . '"'
            . ($tcfEnabled ? ' data-tcf-enabled' : '')
            . ($async ? ' async' : '')
            . '></script>'
        );
        if ($useSDP) {
            $this->pageRenderer->addHeaderData(
                '<script type="application/javascript" id="usercentrics-smart-data-protector" src="https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js"></script>'
            );
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

        foreach (TypoScriptValue::getArray($usercentricsConfig, 'jsInline') as $index => $inLineItem) {
            if (!is_array($inLineItem) || !$this->isJsConfigurationValid($inLineItem)) {
                throw new LiaUsercentricsException('The inline javascript configuration for the index "' . $index . '" is not valid.', 1740577586);
            }

            [$identifier, $attributes, $options] = $this->getTagData($inLineItem);
            $this->assetCollector->addInlineJavaScript($identifier, TypoScriptValue::getString($inLineItem, 'value'), $attributes, $options);
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

        foreach (TypoScriptValue::getArray($usercentricsConfig, 'jsFiles') as $index => $file) {
            if (!is_array($file) || !$this->isJsConfigurationValid($file, true)) {
                throw new LiaUsercentricsException("The JavaScript file configuration in the index '" . $index . "' is not valid", 1740636489);
            }

            [$identifier, $attributes, $options] = $this->getTagData($file);
            $this->assetCollector->addJavaScript($identifier, TypoScriptValue::getString($file, 'file'), $attributes, $options);
        }
    }

    /**
     * Add additional Usercentrics configurations like a custom overlay configuration or configuration for the smart data protector.
     */
    public function addLibraryConfiguration(): void
    {
        $usercentricsSettings = $this->typoscriptReader->getUsercentricsSettings();

        if (!empty($usercentricsSettings['useSmartDataProtector'])) {
            $whitelistedServices = TypoScriptValue::getString($usercentricsSettings, 'whitelistedSDPServices');
            if (!empty($whitelistedServices)) {
                $this->addBlockOnlyInlineScript($whitelistedServices);
            }

            $overlayIntegration = TypoScriptValue::getString($usercentricsSettings, 'customOverlayIntegration');
            if (!empty($overlayIntegration)) {
                $this->customOverlayIntegration($overlayIntegration);
            }

            $reloadOnOptIn = TypoScriptValue::getString($usercentricsSettings, 'reloadOnOptIn');
            if (!empty($reloadOnOptIn)) {
                $this->addReloadOnOptInOrOutConfiguration($reloadOnOptIn);
            }

            $reloadOnOptOut = TypoScriptValue::getString($usercentricsSettings, 'reloadOnOptOut');
            if (!empty($reloadOnOptOut)) {
                $this->addReloadOnOptInOrOutConfiguration($reloadOnOptOut, 'reloadOnOptOut');
            }
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
     * @param array<string, string|int> $tagAttributes
     * @param string $dataProcessingService
     */
    public function getModifiedIframeIntegration(string $src, array $tagAttributes, string $dataProcessingService): string
    {
        return sprintf(
            '<iframe uc-src="%s"%s data-usercentrics="%s"></iframe>',
            htmlspecialchars($src, ENT_QUOTES | ENT_HTML5),
            empty($tagAttributes) ? '' : ' ' . GeneralUtility::implodeAttributes($tagAttributes, true),
            htmlspecialchars($dataProcessingService, ENT_QUOTES | ENT_HTML5)
        );
    }

    /**
     * Check the typoscript configuration of the javascript files or inline configuration.
     * It does not check the content of the configuration.
     *
     * @param array<mixed> $jsConf
     * @param bool $isFile
     */
    protected function isJsConfigurationValid(array $jsConf, bool $isFile = false): bool
    {
        if (!is_string($jsConf['dataProcessingService'] ?? null)) {
            return false;
        }

        if ($isFile) {
            return is_string($jsConf['file'] ?? null);
        }

        return !empty($jsConf['value']) && is_string($jsConf['value']);
    }

    /**
     * Convert the priority value to an boolean.
     *
     * @param array<mixed> $options
     * @return array<mixed>
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
     * @throws LiaUsercentricsException
     */
    protected function customOverlayIntegration(string $overlayIntegration): void
    {
        $customServiceOverlay = $this->buildCustomOverlayJson(CustomOverlayIntegration::parse($overlayIntegration));

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

        $this->pageRenderer->addHeaderData("<script type='text/javascript' id='uc-" . $functionName . "'>" . implode(';', $functionCallList) . '</script>');
    }

    /**
     * Get the needed data for the integration of the configured javascript data.
     *
     * @param array<mixed> $jsConf
     * @return array{string, array<string, string>, array<mixed>}
     */
    private function getTagData(array $jsConf): array
    {
        $dataProcessingService = TypoScriptValue::getString($jsConf, 'dataProcessingService');
        $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
        $attributes = [
            'type' => 'text/plain',
            'data-usercentrics' => $dataProcessingService,
        ];
        $options = $this->convertPriorityToBoolean(TypoScriptValue::getArray($jsConf, 'options'));

        return [$identifier, $attributes, $options];
    }

    /**
     * Generate a json for the Usercentrics custom overlay integration by given typoscript configuration.
     *
     * @param array<string, string> $overlays targets of the data-uc attribute by template ID
     */
    private function buildCustomOverlayJson(array $overlays): string
    {
        $selectors = array_map(static fn(string $target): string => '[data-uc="' . $target . '"]', $overlays);

        return json_encode($selectors, JSON_THROW_ON_ERROR);
    }
}
