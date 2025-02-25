<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\ExpressionLanguage;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;

class UsercentricsConditionProvider extends AbstractProvider
{
    /**
     * Register language expression function for typoscript.
     */
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            'usercentrics' => UsercentricsConditionFunctionProvider::class,
        ];
    }
}
