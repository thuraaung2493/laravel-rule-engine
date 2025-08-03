<?php

namespace Thuraaung\RuleEngine\Dtos;

use Thuraaung\RuleEngine\Models\Rule;

class RuleResult
{
    public function __construct(
        public string $rule,
        public bool $passed,
        public string $expression,
        public ?string $action_type,
        public ?array $action_value,
        public ?string $error_message,
        public ?string $error = null
    ) {}

    public static function create(bool $passed, Rule $rule, ?string $error = null): self
    {
        return new self(
            rule: $rule->name,
            passed: $passed,
            expression: $rule->expression,
            action_type: $rule->action_type,
            action_value: $rule->action_value,
            error_message: $rule->error_message,
            error: $error,
        );
    }
}
