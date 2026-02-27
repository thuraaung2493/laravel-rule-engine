<?php

use Thuraaung\RuleEngine\Dtos\EvaluationResult;
use Thuraaung\RuleEngine\Dtos\RuleResult;
use Thuraaung\RuleEngine\Models\Rule;

describe('EvaluationResult DTO', function (): void {
    it('creates with all parameters', function (): void {
        $rules = collect([
            ['rule' => 'rule1', 'passed' => true],
            ['rule' => 'rule2', 'passed' => false],
        ]);

        $result = new EvaluationResult(
            passed: true,
            rules: collect($rules),
            context: collect(['key' => 'value']),
            error: 'Some error'
        );

        expect($result->passed)->toBe(true);
        expect($result->rules->toArray())->toBe($rules->toArray());
        expect($result->context->toArray())->toBe(['key' => 'value']);
        expect($result->error)->toBe('Some error');
    });

    it('has default values', function (): void {
        $result = new EvaluationResult(passed: false);

        expect($result->passed)->toBe(false);
        expect($result->rules->toArray())->toBe([]);
        expect($result->context->toArray())->toBe([]);
        expect($result->error)->toBeNull();
    });

    it('returns passed status', function (): void {
        $passedResult = new EvaluationResult(passed: true);
        $failedResult = new EvaluationResult(passed: false);

        expect($passedResult->passed())->toBe(true);
        expect($failedResult->passed())->toBe(false);
    });

    it('returns failed rules', function (): void {
        $rule1 = Rule::factory()->noAction()->make(['name' => 'rule1']);
        $rule2 = Rule::factory()->noAction()->make(['name' => 'rule2']);
        $rule3 = Rule::factory()->noAction()->make(['name' => 'rule3']);
        $rules = [
            RuleResult::create(true, $rule1, null),
            RuleResult::create(false, $rule2, null),
            RuleResult::create(false, $rule3, null),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));
        $failedRules = $result->failedRules();

        expect($failedRules)->toHaveCount(2);
        expect($failedRules->first()->rule)->toBe('rule2');
        expect($failedRules->last()->rule)->toBe('rule3');
    });

    it('returns rules with exceptions', function (): void {
        $rule1 = Rule::factory()->noAction()->make(['name' => 'rule1']);
        $rule2 = Rule::factory()->noAction()->make(['name' => 'rule2']);
        $rule3 = Rule::factory()->noAction()->make(['name' => 'rule3']);
        $rules = [
            RuleResult::create(true, $rule1, ''),
            RuleResult::create(false, $rule2, 'Some error'),
            RuleResult::create(false, $rule3, ''),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));
        $rulesWithExceptions = $result->rulesWithExceptions();

        expect($rulesWithExceptions)->toHaveCount(1);
        expect($rulesWithExceptions->first()->rule)->toBe('rule2');
    });

    it('formats failed rules messages with errors', function (): void {
        $rule = Rule::factory()->noAction()->withErrorMessage('')->make(['name' => 'rule1']);
        $rules = [
            RuleResult::create(false, $rule, 'Syntax error'),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));

        $messages = $result->formatFailedRulesMessages();
        expect($messages)->toHaveCount(1);
        expect($messages->first())->toBe('Rule [rule1] failed due to error: Syntax error');
    });

    it('formats failed rules messages with custom message', function (): void {
        $rule = Rule::factory()->noAction()->withErrorMessage('Custom error')->make(['name' => 'rule1']);
        $rules = [
            RuleResult::create(false, $rule, ''),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));

        $messages = $result->formatFailedRulesMessages();
        expect($messages->first())->toBe('Rule [rule1] failed: Custom error');
    });

    it('formats failed rules messages without message', function (): void {
        $rule = Rule::factory()->noAction()->withErrorMessage('')->make(['name' => 'rule1']);
        $rules = [
            RuleResult::create(false, $rule, ''),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));

        $messages = $result->formatFailedRulesMessages();
        expect($messages->first())->toBe('Rule [rule1] failed, but no error message provided.');
    });

    it('formats failed rules as string', function (): void {
        $rule1 = Rule::factory()->noAction()->withErrorMessage('Error 1')->make(['name' => 'rule1']);
        $rule2 = Rule::factory()->noAction()->withErrorMessage('Error 2')->make(['name' => 'rule2']);
        $rules = [
            RuleResult::create(false, $rule1, ''),
            RuleResult::create(false, $rule2, ''),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));

        $string = $result->formatFailedRulesAsString();
        expect($string)->toBe("Rule [rule1] failed: Error 1\nRule [rule2] failed: Error 2");
    });

    it('formats failed rules as string with custom separator', function (): void {
        $rule1 = Rule::factory()->noAction()->withErrorMessage('Error 1')->make(['name' => 'rule1']);
        $rule2 = Rule::factory()->noAction()->withErrorMessage('Error 2')->make(['name' => 'rule2']);
        $rules = [
            RuleResult::create(false, $rule1, ''),
            RuleResult::create(false, $rule2, ''),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));

        $string = $result->formatFailedRulesAsString(' | ');
        expect($string)->toBe('Rule [rule1] failed: Error 1 | Rule [rule2] failed: Error 2');
    });

    it('extracts actions from passed rules', function (): void {
        $rule1 = Rule::factory()->withAction('email', ['to' => 'test@example.com'])->make();
        $rule2 = Rule::factory()->withAction('sms', ['number' => '123'])->make();
        $rule3 = Rule::factory()->withAction('log', ['level' => 'info'])->make();
        $rule4 = Rule::factory()->noAction()->make();

        $rules = [
            RuleResult::create(true, $rule1, null),
            RuleResult::create(false, $rule2, null),
            RuleResult::create(true, $rule3, null),
            RuleResult::create(true, $rule4, null),
        ];

        $result = new EvaluationResult(passed: true, rules: collect($rules));
        $actions = $result->extractActionsFromResult();

        expect($actions)->toHaveCount(2);
        expect($actions[0])->toBe(['action_type' => 'email', 'action_value' => ['to' => 'test@example.com']]);
        expect($actions[1])->toBe(['action_type' => 'log', 'action_value' => ['level' => 'info']]);
    });

    it('returns empty array when no actions available', function (): void {
        $rule1 = Rule::factory()->withAction('email', ['to' => 'test@example.com'])->make();
        $rule2 = Rule::factory()->noAction()->make();

        $rules = [
            RuleResult::create(false, $rule1, null),
            RuleResult::create(true, $rule2, null),
        ];

        $result = new EvaluationResult(passed: false, rules: collect($rules));
        $actions = $result->extractActionsFromResult();

        expect($actions)->toHaveCount(0);
    });
});
