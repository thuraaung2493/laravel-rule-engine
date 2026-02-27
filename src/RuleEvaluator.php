<?php

namespace Thuraaung\RuleEngine;

use Illuminate\Support\Collection;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Thuraaung\RuleEngine\Dtos\RuleResult;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;

class RuleEvaluator
{
    protected ExpressionLanguage $el;

    public function __construct()
    {
        $this->el = new ExpressionLanguage;

        $this->registerExpressionProviders();
    }

    /**
     * Register custom expression providers.
     */
    protected function registerExpressionProviders(): void
    {
        collect(config('rule-engine.expression_providers', []))
            ->filter(
                fn ($provider): bool => class_exists($provider) && is_subclass_of($provider, ExpressionFunctionProviderInterface::class)
            )
            ->each(function ($provider): void {
                $this->el->registerProvider(app($provider, ['el' => $this->el]));
            });
    }

    /**
     * Evaluate all rules in a group.
     *
     * @return Collection<RuleResult>
     */
    public function evaluateRules(RuleGroup $ruleGroup, array $data): Collection
    {
        $results = collect();

        $ruleGroup->rules->each(function (Rule $rule) use ($data, $ruleGroup, $results) {
            $ruleResult = $this->evaluateRule($rule, $data);
            $results->push($ruleResult);

            if (EvaluationLogic::FAIL_FAST->is($ruleGroup->evaluation_logic) && ! $ruleResult->passed) {
                return false;
            }
        });

        return $results;
    }

    /**
     * Evaluate a single rule.
     */
    public function evaluateRule(Rule $rule, array $data): RuleResult
    {
        try {
            $passed = (bool) $this->el->evaluate($rule->expression, $data);
            $error = null;
        } catch (\Throwable $throwable) {
            $passed = false;
            $error = $throwable->getMessage();
        }

        return RuleResult::create($passed, $rule, $error);
    }
}
