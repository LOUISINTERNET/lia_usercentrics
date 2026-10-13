<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Services\Integration;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;
use LIA\LiaUsercentrics\Services\TypoScript\TypoScriptValue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class ConsentIntegrationConfiguration
{
    /**
     * @param array<string, string> $overlays
     */
    private function __construct(private array $overlays) {}

    /**
     * @param array<mixed> $settings
     * @throws LiaUsercentricsException
     */
    public static function fromSettings(array $settings): self
    {
        if (!empty(TypoScriptValue::getString($settings, 'useSmartDataProtector'))) {
            throw new LiaUsercentricsException(
                'The constants "useConsentIntegration" and "useSmartDataProtector" cannot both be enabled, disable one of them in plugin.tx_liausercentrics.settings.',
                1791905585
            );
        }

        foreach (['reloadOnOptIn', 'reloadOnOptOut'] as $constantName) {
            if (TypoScriptValue::getString($settings, $constantName) !== '') {
                throw new LiaUsercentricsException(
                    sprintf('The constant "%s" is only supported with the Smart Data Protector, remove it when "useConsentIntegration" is enabled.', $constantName),
                    1791905586
                );
            }
        }

        return new self(self::parseOverlays(TypoScriptValue::getString($settings, 'customOverlayIntegration')));
    }

    public function toJson(): string
    {
        return json_encode(['overlays' => (object)$this->overlays], JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     * @throws LiaUsercentricsException
     */
    private static function parseOverlays(string $customOverlayIntegration): array
    {
        $overlays = [];
        foreach (GeneralUtility::trimExplode(',', $customOverlayIntegration, true) as $entry) {
            $parts = GeneralUtility::trimExplode(':', $entry);
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                throw new LiaUsercentricsException(
                    sprintf('The entry "%s" in the constant "customOverlayIntegration" is not valid, expected "<TemplateID>:<target>".', $entry),
                    1791905587
                );
            }

            $overlays[$parts[0]] = $parts[1];
        }

        return $overlays;
    }
}
