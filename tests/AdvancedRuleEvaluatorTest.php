<?php

use Thuraaung\RuleEngine\RuleEvaluator;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;

describe('Advanced Rule Evaluation Edge Cases', function () {
    beforeEach(function () {
        $this->evaluator = new RuleEvaluator();
    });

    it('handles deeply nested objects and arrays', function () {
        $rule = Rule::factory()->make([
            'name' => 'nested_data_rule',
            'expression' => 'user["profile"]["settings"]["notifications"]["email"] == true && user["roles"][0]["level"] > 5',
        ]);

        $data = [
            'user' => [
                'profile' => [
                    'settings' => [
                        'notifications' => [
                            'email' => true
                        ]
                    ]
                ],
                'roles' => [
                    ['level' => 7],
                    ['level' => 3],
                    ['level' => 4]
                ]
            ]
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result['passed'])->toBeTrue();
    });

    it('handles recursive array operations', function () {
        $rule = Rule::factory()->make([
            'name' => 'recursive_array_rule',
            'expression' => 'count(array_filter(permissions, "p > 5")) == 2',
        ]);

        $data = [
            'permissions' => [3, 6, 8, 4]
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result['passed'])->toBeTrue();
    });

    it('handles type juggling edge cases', function () {
        $rule = Rule::factory()->make([
            'name' => 'type_juggling_rule',
            'expression' => 'value1 === "0" && value2 === 0 && value3 === false && value4 === null',
        ]);

        $data = [
            'value1' => '0',
            'value2' => 0,
            'value3' => false,
            'value4' => null
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result['passed'])->toBeTrue();
    });

    it('handles unicode and special characters in expressions', function () {
        $rule = Rule::factory()->make([
            'name' => 'unicode_rule',
            'expression' => 'name == "João 🚀" && category == "café"',
        ]);

        $data = [
            'name' => 'João 🚀',
            'category' => 'café'
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result['passed'])->toBeTrue();
    });

    it('handles complex date and time comparisons', function () {
        $rule = Rule::factory()->make([
            'name' => 'date_time_rule',
            'expression' => 'strtotime(date) >= strtotime("-1 week") && strtotime(date) <= strtotime("now")',
        ]);

        $data = [
            'date' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result['passed'])->toBeTrue();
    });

    it('handles complex mathematical expressions', function () {
        $rule = Rule::factory()->make([
            'name' => 'math_rule',
            'expression' => 'pow(base, 2) + floor(decimal) == 30 && sqrt(number) >= 2',
        ]);

        $data = [
            'base' => 5,
            'number' => 5,
            'decimal' => 5.0
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result['passed'])->toBeTrue();
    });
});
