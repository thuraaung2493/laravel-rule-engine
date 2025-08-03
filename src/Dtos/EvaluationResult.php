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
            ->filter(fn ($rule) => ! $rule['passed'])
            ->values();
    }

    public function rulesWithExceptions(): Collection
    {
        return $this->rules
            ->filter(fn ($rule) => ! empty($rule['error']))
            ->values();
    }

    public function formatFailedRulesMessages(): Collection
    {
        return $this->failedRules()
            ->map(function ($rule) {
                if (! empty($rule['error'])) {
                    return __('rule-engine::messages.rule_failed_error', [
                        'rule' => $rule['rule'],
                        'error' => $rule['error'],
                    ]);
                }

                if (! empty($rule['error_message'])) {
                    return __('rule-engine::messages.rule_failed', [
                        'rule' => $rule['rule'],
                        'message' => $rule['error_message'],
                    ]);
                }

                return __('rule-engine::messages.rule_failed_no_message', [
                    'rule' => $rule['rule'],
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
            ->filter(fn ($rule) => $rule['passed'])
            ->filter(fn ($rule) => $rule['action_type'] !== null)
            ->map(fn ($rule) => [
                'action_type' => $rule['action_type'],
                'action_value' => $rule['action_value'],
            ])
            ->values();
    }
}
