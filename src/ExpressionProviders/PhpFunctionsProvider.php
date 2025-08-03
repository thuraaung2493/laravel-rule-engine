<?php

namespace Thuraaung\RuleEngine\ExpressionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PhpFunctionsProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(
        protected ExpressionLanguage $el,
    ) {}

    protected array $functions = [
        'count',
        'strtotime',
        'date',
        'pow',
        'sqrt',
        'floor',
        'ceil',
        'round',
        'strlen',
        'substr',
        'trim',
        'strtolower',
        'strtoupper',
        'str_contains',
        'is_null',
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

        // Add array_filter with special handling
        $functions->push(
            new ExpressionFunction(
                'array_filter',
                fn () => 'array_filter',
                function ($arguments, $array, $callback) {
                    if (! is_array($array)) {
                        return [];
                    }
                    try {
                        return array_filter($array, function ($item) use ($callback) {
                            return $this->el->evaluate($callback, ['p' => $item]);
                        });
                    } catch (\Throwable $e) {
                        return [];
                    }
                }
            )
        );

        return $functions->all();
    }
}
