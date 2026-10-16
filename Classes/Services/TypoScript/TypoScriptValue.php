<?php

declare(strict_types=1);

/*
 * This file is part of the "LIA Usercentrics" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace LIA\LiaUsercentrics\Services\TypoScript;

use LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException;

final class TypoScriptValue
{
    /**
     * @param array<mixed> $values
     * @throws LiaUsercentricsException
     */
    public static function getString(array $values, string $key): string
    {
        $value = $values[$key] ?? '';
        if (!is_string($value)) {
            throw new LiaUsercentricsException(
                sprintf('The TypoScript value "%s" must be a string, %s given.', $key, get_debug_type($value)),
                1791905166
            );
        }

        return $value;
    }

    /**
     * @param array<mixed> $values
     * @return array<mixed>
     * @throws LiaUsercentricsException
     */
    public static function getArray(array $values, string $key): array
    {
        $value = $values[$key] ?? [];
        if (!is_array($value)) {
            throw new LiaUsercentricsException(
                sprintf('The TypoScript value "%s" must be an array, %s given.', $key, get_debug_type($value)),
                1791905167
            );
        }

        return $value;
    }
}
