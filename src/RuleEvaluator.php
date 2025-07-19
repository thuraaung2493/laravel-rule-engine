<?php

namespace Thuraaung\RuleEngine;

use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Illuminate\Support\Collection;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class RuleEvaluator
{
    protected ExpressionLanguage $el;

    public function __construct()
    {
        $this->el = new ExpressionLanguage();
        $this->registerExpressionProviders();
    }

    protected function registerExpressionProviders(): void
    {
        collect(config('rule-engine.expression_providers', []))
            ->filter(
                fn($provider) => class_exists($provider) && is_subclass_of($provider, ExpressionFunctionProviderInterface::class)
            )
            ->each(function ($provider) {
                $this->el->registerProvider(new $provider());
            });
    }

    public function evaluateRules(RuleGroup $group, array $data): Collection
    {
        return $group->rules->reduce(function (Collection $results, Rule $rule) use ($data, $group) {
            $result = $this->evaluateRule($rule, $data);
            $results->push($result);

            if (EvaluationLogic::FAIL_FAST->is($group->evaluation_logic) && !$result['passed']) {
                return $results;
            }

            return $results;
        }, collect());
    }

    public function evaluateRule(Rule $rule, array $data): array
    {
        try {
            $passed = (bool) $this->el->evaluate($rule->expression, $data);
            $error = null;
        } catch (\Throwable $e) {
            $passed = false;
            $error = $e->getMessage();
        }

        return [
            'rule' => $rule->name,
            'passed' => $passed,
            'expression' => $rule->expression,
            'action_type' => $rule->action_type,
            'action_value' => $rule->action_value,
            'error_message' => $rule->error_message,
            'error' => $error,
        ];
    }
}
