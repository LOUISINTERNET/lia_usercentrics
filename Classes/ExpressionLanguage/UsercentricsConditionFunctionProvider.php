<?php

declare(strict_types=1);

namespace LIA\LiaUsercentrics\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class UsercentricsConditionFunctionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * Provide functions for the typoscript.
     */
    public function getFunctions(): array
    {
        return [
            $this->getUsercentricsIsActiveFunction(),
        ];
    }

    /**
     * Provide the function to check if Usercentrics active is true in the setup.typoscript.
     */
    protected function getUsercentricsIsActiveFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'usercentricsIsActive',
            static fn() => null,
            static function (array $arguments, bool $activate, string $settingsId) {
                return $activate && !empty($settingsId);
            }
        );
    }
}
