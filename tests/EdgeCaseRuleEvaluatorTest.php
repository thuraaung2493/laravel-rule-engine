<?php

use Thuraaung\RuleEngine\RuleEvaluator;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;

it('evaluates complex nested expressions', function () {
    $evaluator = new RuleEvaluator();

    $rule = Rule::factory()->make([
        'name' => 'complex_rule',
        'expression' => '(age >= 18 && country == "US") || (age >= 21 && country == "UK")',
    ]);

    // US user under 21
    $result1 = $evaluator->evaluateRule($rule, ['age' => 19, 'country' => 'US']);
    expect($result1['passed'])->toBeTrue();

    // UK user under 21
    $result2 = $evaluator->evaluateRule($rule, ['age' => 19, 'country' => 'UK']);
    expect($result2['passed'])->toBeFalse();

    // UK user over 21
    $result3 = $evaluator->evaluateRule($rule, ['age' => 22, 'country' => 'UK']);
    expect($result3['passed'])->toBeTrue();
});

it('handles missing context variables gracefully', function () {
    $evaluator = new RuleEvaluator();

    $rule = Rule::factory()->make([
        'name' => 'missing_var_rule',
        'expression' => 'missing_var > 0',
    ]);

    $result = $evaluator->evaluateRule($rule, []);
    expect($result['passed'])->toBeFalse();
    expect($result['error'])->toContain('missing_var');
});

it('handles special PHP values in expressions', function () {
    $evaluator = new RuleEvaluator();

    $rule = Rule::factory()->make([
        'name' => 'special_values_rule',
        'expression' => 'value === null || value === true || value === false',
    ]);

    $result1 = $evaluator->evaluateRule($rule, ['value' => null]);
    expect($result1['passed'])->toBeTrue();

    $result2 = $evaluator->evaluateRule($rule, ['value' => true]);
    expect($result2['passed'])->toBeTrue();

    $result3 = $evaluator->evaluateRule($rule, ['value' => false]);
    expect($result3['passed'])->toBeTrue();

    $result4 = $evaluator->evaluateRule($rule, ['value' => 0]);
    expect($result4['passed'])->toBeFalse();
});

it('evaluates rules with array operations', function () {
    $evaluator = new RuleEvaluator();

    $rule = Rule::factory()->make([
        'name' => 'array_rule',
        'expression' => 'roles[0] == "admin" || permissions contains "edit"',
    ]);

    $result1 = $evaluator->evaluateRule($rule, [
        'roles' => ['admin', 'user'],
        'permissions' => ['view', 'edit', 'delete']
    ]);
    expect($result1['passed'])->toBeTrue();

    $result2 = $evaluator->evaluateRule($rule, [
        'roles' => ['user'],
        'permissions' => ['view']
    ]);
    expect($result2['passed'])->toBeFalse();
});

it('handles syntax errors in expressions', function () {
    $evaluator = new RuleEvaluator();

    $rule = Rule::factory()->make([
        'name' => 'invalid_syntax_rule',
        'expression' => '!!!invalid!!!syntax!!!', // Invalid syntax
    ]);

    $result = $evaluator->evaluateRule($rule, ['x' => 1]);
    expect($result['passed'])->toBeFalse();
    expect($result['error'])->toBeString();
});

it('respects evaluation order in complex group scenarios', function () {
    $evaluator = new RuleEvaluator();

    $group = RuleGroup::factory()->make([
        'evaluation_logic' => EvaluationLogic::FAIL_FAST,
    ]);

    $rule1 = Rule::factory()->make([
        'name' => 'rule1',
        'expression' => 'false',
        'priority' => 100,
    ]);

    $rule2 = Rule::factory()->make([
        'name' => 'rule2',
        'expression' => 'true',
        'priority' => 50,
    ]);

    $group->setRelation('rules', collect([$rule1, $rule2]));

    $results = $evaluator->evaluateRules($group, []);
    expect($results)->toHaveCount(1); // Should stop after first failure
    expect($results[0]['rule'])->toBe('rule1');
    expect($results[0]['passed'])->toBeFalse();
});
