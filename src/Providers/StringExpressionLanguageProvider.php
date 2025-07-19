<?php

namespace Thuraaung\RuleEngine\Providers;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class StringExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('lowercase', function ($str): string {
                return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $str);
            }, function ($arguments, $str): string {
                if (!is_string($str)) {
                    return $str;
                }

                return strtolower($str);
            }),
        ];
    }
}
