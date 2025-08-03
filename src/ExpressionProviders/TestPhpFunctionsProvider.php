<?php

namespace Thuraaung\RuleEngine\ExpressionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class TestPhpFunctionsProvider implements ExpressionFunctionProviderInterface
{
    protected array $functions = [
        'empty',
        'isset',
    ];

    public function getFunctions(): array
    {
        $functions = collect($this->functions)->map(function (string $function) {
            return new ExpressionFunction(
                $function,
                fn () => $function,
                fn ($arguments, ...$params) => $function(...$params)
            );
        });

        return $functions->all();
    }
}
