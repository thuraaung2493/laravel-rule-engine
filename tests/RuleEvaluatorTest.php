<?php

use Thuraaung\RuleEngine\Dtos\RuleResult;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\RuleEvaluator;

describe('RuleEvaluator', function () {
    beforeEach(function () {
        $this->evaluator = new RuleEvaluator;
    });

    it('evaluates a single rule successfully', function () {
        $rule = Rule::factory()->make([
            'name' => 'age_check',
            'expression' => 'age >= 18',
            'action_type' => 'allow',
            'action_value' => ['permission' => 'full'],
            'error_message' => 'Must be 18 or older',
        ]);

        $data = ['age' => 25];
        $result = $this->evaluator->evaluateRule($rule, $data);

        expect($result)->toEqual(RuleResult::create(true, $rule));
    });

    it('evaluates a single rule that fails', function () {
        $rule = Rule::factory()->make([
            'name' => 'age_check',
            'expression' => 'age >= 18',
            'action_type' => 'deny',
            'action_value' => null,
            'error_message' => 'Must be 18 or older',
        ]);

        $data = ['age' => 16];
        $result = $this->evaluator->evaluateRule($rule, $data);

        expect($result->rule)->toBe('age_check');
        expect($result->passed)->toBe(false);
        expect($result->error)->toBeNull();
    });

    it('handles expression evaluation errors', function () {
        $rule = Rule::factory()->make([
            'name' => 'invalid_rule',
            'expression' => 'invalid_variable_name >= 18',
            'action_type' => null,
            'action_value' => null,
            'error_message' => 'Invalid rule',
        ]);

        $data = ['age' => 25];
        $result = $this->evaluator->evaluateRule($rule, $data);

        expect($result->rule)->toBe('invalid_rule');
        expect($result->passed)->toBe(false);
        expect($result->error)->toBeString();
    });

    it('evaluates multiple rules in a group', function () {
        $group = RuleGroup::factory()->make([
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $rule1 = Rule::factory()->make([
            'name' => 'age_check',
            'expression' => 'age >= 18',
            'priority' => 10,
        ]);

        $rule2 = Rule::factory()->make([
            'name' => 'country_check',
            'expression' => 'country == "US"',
            'priority' => 5,
        ]);

        $group->setRelation('rules', collect([$rule1, $rule2]));

        $data = ['age' => 25, 'country' => 'US'];
        $results = $this->evaluator->evaluateRules($group, $data);

        expect($results)->toHaveCount(2);
        expect($results->first()->rule)->toBe('age_check');
        expect($results->first()->passed)->toBe(true);
        expect($results->last()->rule)->toBe('country_check');
        expect($results->last()->passed)->toBe(true);
    });

    it('stops evaluation on first failure with FAIL_FAST logic', function () {
        $group = RuleGroup::factory()->make([
            'evaluation_logic' => EvaluationLogic::FAIL_FAST,
        ]);

        $rule1 = Rule::factory()->make([
            'name' => 'age_check',
            'expression' => 'age >= 18',
            'priority' => 10,
        ]);

        $rule2 = Rule::factory()->make([
            'name' => 'country_check',
            'expression' => 'country == "US"',
            'priority' => 5,
        ]);

        $group->setRelation('rules', collect([$rule1, $rule2]));

        $data = ['age' => 16, 'country' => 'US']; // First rule fails
        $results = $this->evaluator->evaluateRules($group, $data);

        expect($results)->toHaveCount(1);
        expect($results->first()->rule)->toBe('age_check');
        expect($results->first()->passed)->toBe(false);
    });

    it('continues evaluation with other logic types even on failure', function () {
        $group = RuleGroup::factory()->make([
            'evaluation_logic' => EvaluationLogic::ANY,
        ]);

        $rule1 = Rule::factory()->make([
            'name' => 'age_check',
            'expression' => 'age >= 18',
            'priority' => 10,
        ]);

        $rule2 = Rule::factory()->make([
            'name' => 'country_check',
            'expression' => 'country == "US"',
            'priority' => 5,
        ]);

        $group->setRelation('rules', collect([$rule1, $rule2]));

        $data = ['age' => 16, 'country' => 'US']; // First rule fails
        $results = $this->evaluator->evaluateRules($group, $data);

        expect($results)->toHaveCount(2); // Both rules evaluated
        expect($results->first()->passed)->toBe(false);
        expect($results->last()->passed)->toBe(true);
    });

    it('handles empty rule group', function () {
        $group = RuleGroup::factory()->make([
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $group->setRelation('rules', collect([]));

        $data = ['age' => 25];
        $results = $this->evaluator->evaluateRules($group, $data);

        expect($results)->toHaveCount(0);
    });

    it('handles complex expressions', function () {
        $rule = Rule::factory()->make([
            'name' => 'complex_check',
            'expression' => '(age >= 18 and country == "US") or (age >= 21 and country == "CA")',
        ]);

        $data1 = ['age' => 19, 'country' => 'US'];
        $result1 = $this->evaluator->evaluateRule($rule, $data1);
        expect($result1->passed)->toBeTrue();

        $data2 = ['age' => 19, 'country' => 'CA'];
        $result2 = $this->evaluator->evaluateRule($rule, $data2);
        expect($result2->passed)->toBeFalse();

        $data3 = ['age' => 22, 'country' => 'CA'];
        $result3 = $this->evaluator->evaluateRule($rule, $data3);
        expect($result3->passed)->toBeTrue();
    });

    it('handles missing context variables gracefully', function () {
        $evaluator = new RuleEvaluator;

        $rule = Rule::factory()->make([
            'name' => 'missing_var_rule',
            'expression' => 'missing_var > 0',
        ]);

        $result = $evaluator->evaluateRule($rule, []);
        expect($result->passed)->toBeFalse();
        expect($result->error)->toContain('missing_var');
    });

    it('handles special PHP values in expressions', function () {
        $evaluator = new RuleEvaluator;

        $rule = Rule::factory()->make([
            'name' => 'special_values_rule',
            'expression' => 'value === null || value === true || value === false',
        ]);

        $result1 = $evaluator->evaluateRule($rule, ['value' => null]);
        expect($result1->passed)->toBeTrue();

        $result2 = $evaluator->evaluateRule($rule, ['value' => true]);
        expect($result2->passed)->toBeTrue();

        $result3 = $evaluator->evaluateRule($rule, ['value' => false]);
        expect($result3->passed)->toBeTrue();

        $result4 = $evaluator->evaluateRule($rule, ['value' => 0]);
        expect($result4->passed)->toBeFalse();
    });

    it('evaluates rules with array operations', function () {
        $evaluator = new RuleEvaluator;

        $rule = Rule::factory()->make([
            'name' => 'array_rule',
            'expression' => 'roles[0] == "admin" || permissions contains "edit"',
        ]);

        $result1 = $evaluator->evaluateRule($rule, [
            'roles' => ['admin', 'user'],
            'permissions' => ['view', 'edit', 'delete'],
        ]);
        expect($result1->passed)->toBeTrue();

        $result2 = $evaluator->evaluateRule($rule, [
            'roles' => ['user'],
            'permissions' => ['view'],
        ]);
        expect($result2->passed)->toBeFalse();
    });

    it('handles syntax errors in expressions', function () {
        $evaluator = new RuleEvaluator;

        $rule = Rule::factory()->make([
            'name' => 'invalid_syntax_rule',
            'expression' => '!!!invalid!!!syntax!!!', // Invalid syntax
        ]);

        $result = $evaluator->evaluateRule($rule, ['x' => 1]);
        expect($result->passed)->toBeFalse();
        expect($result->error)->toBeString();
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
                            'email' => true,
                        ],
                    ],
                ],
                'roles' => [
                    ['level' => 7],
                    ['level' => 3],
                    ['level' => 4],
                ],
            ],
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result->passed)->toBeTrue();
    });

    it('handles recursive array operations', function () {
        $rule = Rule::factory()->make([
            'name' => 'recursive_array_rule',
            'expression' => 'count(array_filter(permissions, "p > 5")) == 2',
        ]);

        $data = [
            'permissions' => [3, 6, 8, 4],
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result->passed)->toBeTrue();
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
            'value4' => null,
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result->passed)->toBeTrue();
    });

    it('handles unicode and special characters in expressions', function () {
        $rule = Rule::factory()->make([
            'name' => 'unicode_rule',
            'expression' => 'name == "João 🚀" && category == "café"',
        ]);

        $data = [
            'name' => 'João 🚀',
            'category' => 'café',
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result->passed)->toBeTrue();
    });

    it('handles complex date and time comparisons', function () {
        $rule = Rule::factory()->make([
            'name' => 'date_time_rule',
            'expression' => 'strtotime(date) >= strtotime("-1 week") && strtotime(date) <= strtotime("now")',
        ]);

        $data = [
            'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result->passed)->toBeTrue();
    });

    it('handles complex mathematical expressions', function () {
        $rule = Rule::factory()->make([
            'name' => 'math_rule',
            'expression' => 'pow(base, 2) + floor(decimal) == 30 && sqrt(number) >= 2',
        ]);

        $data = [
            'base' => 5,
            'number' => 5,
            'decimal' => 5.0,
        ];

        $result = $this->evaluator->evaluateRule($rule, $data);
        expect($result->passed)->toBeTrue();
    });
});
