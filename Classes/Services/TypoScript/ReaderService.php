<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\Services\TypoScript;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Psr\Http\Message\ServerRequestInterface;

final class ReaderService
{
    /**
     * Service constructor.
     */
    public function __construct(private ConfigurationManager $configurationManager, private TypoScriptService $typoscriptService) {}

    /**
     * Returns the Usercentrics typoscript configuration.
     *
     * @throws LiaUsercentricsException
     */
    public function getUsercentricsConfiguration(): array
    {
        $fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $fullTypoScript = $this->evaluateLanguageContentObject($fullTypoScript);

        $configuration = $this->typoscriptService->convertTypoScriptArrayToPlainArray($fullTypoScript)['plugin']['tx_liausercentrics'] ?? [];

        if (empty($configuration)) {
            throw new LiaUsercentricsException('The typoscript configuration is empty. Make sure that it is loaded in the static template.', 1740654841);
        }

        return $configuration;
    }

    /**
     * Returns only the settings array.
     *
     * @throws LiaUsercentricsException
     */
    public function getUsercentricsSettings(): array
    {
        $settings = $this->typoscriptService->convertTypoScriptArrayToPlainArray(
            $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
        )['plugin']['tx_liausercentrics']['settings'] ?? [];

        if (empty($settings)) {
            throw new LiaUsercentricsException('The Usercentrics settings are not set.', 1741944668);
        }

        return $settings;
    }

    /**
     * Evaluate TypoScript cObject "plugin.tx_liausercentrics.language"
     * using a request-aware ContentObjectRenderer.
     *
     * @param array $fullTypoScript
     */
    private function evaluateLanguageContentObject(array $fullTypoScript): array
    {
        $pluginConfiguration = $fullTypoScript['plugin.']['tx_liausercentrics.'] ?? null;
        if (!is_array($pluginConfiguration)) {
            return $fullTypoScript;
        }

        if (!isset($pluginConfiguration['language.']) || !is_array($pluginConfiguration['language.'])) {
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

        $evaluatedLanguage = trim((string)$this->getContentObjectRenderer()->cObjGetSingle(
            $contentObjectName,
            $pluginConfiguration['language.'],
            'plugin.tx_liausercentrics.language'
        ));

        $languageValue = $evaluatedLanguage !== '' ? $evaluatedLanguage : $fallbackLanguage;

        $fullTypoScript['plugin.']['tx_liausercentrics.']['language'] = $languageValue;
        unset($fullTypoScript['plugin.']['tx_liausercentrics.']['language.']);

        return $fullTypoScript;
    }

    /**
     * Return a request-aware ContentObjectRenderer
     */
    private function getContentObjectRenderer(): ContentObjectRenderer
    {
        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        if (!empty($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            $cObj->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        return $cObj;
    }
}
