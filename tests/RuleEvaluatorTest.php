<?php

use Thuraaung\RuleEngine\RuleEvaluator;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;

describe('RuleEvaluator', function () {
    beforeEach(function () {
        $this->evaluator = new RuleEvaluator();
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

        expect($result)->toBe([
            'rule' => 'age_check',
            'passed' => true,
            'expression' => 'age >= 18',
            'action_type' => 'allow',
            'action_value' => ['permission' => 'full'],
            'error_message' => 'Must be 18 or older',
            'error' => null,
        ]);
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

        expect($result['rule'])->toBe('age_check');
        expect($result['passed'])->toBe(false);
        expect($result['error'])->toBeNull();
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

        expect($result['rule'])->toBe('invalid_rule');
        expect($result['passed'])->toBe(false);
        expect($result['error'])->toBeString();
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
        expect($results[0]['rule'])->toBe('age_check');
        expect($results[0]['passed'])->toBe(true);
        expect($results[1]['rule'])->toBe('country_check');
        expect($results[1]['passed'])->toBe(true);
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

        expect($results)->toHaveCount(1); // Only first rule evaluated
        expect($results[0]['rule'])->toBe('age_check');
        expect($results[0]['passed'])->toBe(false);
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
        expect($results[0]['passed'])->toBe(false);
        expect($results[1]['passed'])->toBe(true);
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
        expect($result1['passed'])->toBe(true);

        $data2 = ['age' => 19, 'country' => 'CA'];
        $result2 = $this->evaluator->evaluateRule($rule, $data2);
        expect($result2['passed'])->toBe(false);

        $data3 = ['age' => 22, 'country' => 'CA'];
        $result3 = $this->evaluator->evaluateRule($rule, $data3);
        expect($result3['passed'])->toBe(true);
    });
});
