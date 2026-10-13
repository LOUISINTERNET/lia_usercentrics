<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Services\TypoScript;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class ReaderService
{
    /**
     * Service constructor.
     */
    public function __construct(private ConfigurationManagerInterface $configurationManager, private TypoScriptService $typoscriptService) {}

    /**
     * Returns the Usercentrics typoscript configuration.
     *
     * @return array<mixed>
     * @throws LiaUsercentricsException
     */
    public function getUsercentricsConfiguration(): array
    {
        $fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $configuration = $this->getPluginConfiguration($this->evaluateLanguageContentObject($fullTypoScript));

        if (empty($configuration)) {
            throw new LiaUsercentricsException('The typoscript configuration is empty. Make sure that it is loaded in the static template.', 1740654841);
        }

        return $configuration;
    }

    /**
     * Returns only the settings array.
     *
     * @return array<mixed>
     * @throws LiaUsercentricsException
     */
    public function getUsercentricsSettings(): array
    {
        $configuration = $this->getPluginConfiguration(
            $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
        );
        $settings = $configuration['settings'] ?? [];

        if (!is_array($settings) || empty($settings)) {
            throw new LiaUsercentricsException('The Usercentrics settings are not set.', 1741944668);
        }

        return $settings;
    }

    /**
     * @param array<mixed> $fullTypoScript
     * @return array<mixed>
     */
    private function getPluginConfiguration(array $fullTypoScript): array
    {
        $plugins = $this->typoscriptService->convertTypoScriptArrayToPlainArray($fullTypoScript)['plugin'] ?? [];
        $configuration = is_array($plugins) ? ($plugins['tx_liausercentrics'] ?? []) : [];

        return is_array($configuration) ? $configuration : [];
    }

    /**
     * Evaluate TypoScript cObject "plugin.tx_liausercentrics.language"
     * using a request-aware ContentObjectRenderer.
     *
     * @param array<mixed> $fullTypoScript
     * @return array<mixed>
     */
    private function evaluateLanguageContentObject(array $fullTypoScript): array
    {
        $plugins = $fullTypoScript['plugin.'] ?? null;
        if (!is_array($plugins)) {
            return $fullTypoScript;
        }

        $pluginConfiguration = $plugins['tx_liausercentrics.'] ?? null;
        if (!is_array($pluginConfiguration)) {
            return $fullTypoScript;
        }

        $languageConfiguration = $pluginConfiguration['language.'] ?? null;
        if (!is_array($languageConfiguration)) {
            return $fullTypoScript;
        }

        $contentObjectName = $pluginConfiguration['language'] ?? 'TEXT';
        if (!is_string($contentObjectName) || $contentObjectName === '') {
            $contentObjectName = 'TEXT';
        }

        $fallbackLanguage = $pluginConfiguration['language'] ?? '';
        if (!is_string($fallbackLanguage)) {
            $fallbackLanguage = '';
        }

        $evaluatedLanguage = trim($this->getContentObjectRenderer()->cObjGetSingle(
            $contentObjectName,
            $languageConfiguration,
            'plugin.tx_liausercentrics.language'
        ));

        $pluginConfiguration['language'] = $evaluatedLanguage !== '' ? $evaluatedLanguage : $fallbackLanguage;
        unset($pluginConfiguration['language.']);
        $plugins['tx_liausercentrics.'] = $pluginConfiguration;
        $fullTypoScript['plugin.'] = $plugins;

        return $fullTypoScript;
    }

    /**
     * Return a request-aware ContentObjectRenderer
     */
    private function getContentObjectRenderer(): ContentObjectRenderer
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request instanceof ServerRequestInterface) {
            $contentObjectRenderer->setRequest($request);
        }

        return $contentObjectRenderer;
    }
}
