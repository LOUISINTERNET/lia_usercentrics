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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Parses the constant "customOverlayIntegration" for the Smart Data Protector and the consent integration.
 */
final class CustomOverlayIntegration
{
    private const ENTRY_PATTERN = '/^([A-Za-z0-9_-]+)\s*:\s*([A-Za-z0-9_-]+)$/';

    /**
     * Returns the targets of the data-uc attribute by template ID.
     *
     * @return array<string, string>
     * @throws LiaUsercentricsException
     */
    public static function parse(string $customOverlayIntegration): array
    {
        $overlays = [];
        foreach (GeneralUtility::trimExplode(',', $customOverlayIntegration, true) as $entry) {
            if (preg_match(self::ENTRY_PATTERN, $entry, $matches) !== 1) {
                throw new LiaUsercentricsException(
                    sprintf('The entry "%s" in the constant "customOverlayIntegration" is not valid, expected "<TemplateID>:<target>" with the characters A-Z, a-z, 0-9, "_" and "-".', $entry),
                    1791905587
                );
            }

            $overlays[$matches[1]] = $matches[2];
        }

        return $overlays;
    }
}
