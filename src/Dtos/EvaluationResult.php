<?php

namespace Thuraaung\RuleEngine\Dtos;

use Illuminate\Support\Collection;

class EvaluationResult
{
    public function __construct(
        public bool $passed,
        public ?Collection $rules = null,
        public ?Collection $context = null,
        public ?string $error = null
    ) {
        $this->rules = $rules ?? collect();
        $this->context = $context ?? collect();
    }

    public static function create(bool $passed, Collection $rules, Collection $context): self
    {
        return new self(
            passed: $passed,
            rules: $rules,
            context: $context
        );
    }

    public static function error(string $error): self
    {
        return new self(
            passed: false,
            error: $error
        );
    }

    public function passed(): bool
    {
        return $this->passed;
    }

    public function failedRules(): Collection
    {
        return $this->rules
            ->filter(fn (RuleResult $ruleResult): bool => ! $ruleResult->passed)
            ->values();
    }

    public function rulesWithExceptions(): Collection
    {
        return $this->rules
            ->filter(fn (RuleResult $ruleResult): bool => $ruleResult->error !== null && $ruleResult->error !== '' && $ruleResult->error !== '0')
            ->values();
    }

    public function formatFailedRulesMessages(): Collection
    {
        return $this->failedRules()
            ->map(function (RuleResult $ruleResult) {
                if ($ruleResult->error !== null && $ruleResult->error !== '' && $ruleResult->error !== '0') {
                    return __('rule-engine::messages.rule_failed_error', [
                        'rule' => $ruleResult->rule,
                        'error' => $ruleResult->error,
                    ]);
                }

                if ($ruleResult->error_message !== null && $ruleResult->error_message !== '' && $ruleResult->error_message !== '0') {
                    return __('rule-engine::messages.rule_failed', [
                        'rule' => $ruleResult->rule,
                        'message' => $ruleResult->error_message,
                    ]);
                }

                return __('rule-engine::messages.rule_failed_no_message', [
                    'rule' => $ruleResult->rule,
                ]);
            })
            ->values();
    }

    public function formatFailedRulesAsString(string $separator = "\n"): string
    {
        return $this->formatFailedRulesMessages()->implode($separator);
    }

    public function extractActionsFromResult(): Collection
    {
        return $this->rules
            ->filter(fn (RuleResult $ruleResult): bool => $ruleResult->passed)
            ->filter(fn (RuleResult $ruleResult): bool => $ruleResult->action_type !== null)
            ->map(fn (RuleResult $ruleResult): array => [
                'action_type' => $ruleResult->action_type,
                'action_value' => $ruleResult->action_value,
            ])
            ->values();
    }
}
