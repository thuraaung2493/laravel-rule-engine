<?php

use Thuraaung\RuleEngine\Dtos\EvaluationResult;

describe('EvaluationResult DTO', function () {
    it('creates with all parameters', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => true],
            ['rule' => 'rule2', 'passed' => false],
        ];

        $result = new EvaluationResult(
            passed: true,
            rules: $rules,
            context: ['key' => 'value'],
            error: 'Some error'
        );

        expect($result->passed)->toBe(true);
        expect($result->rules)->toBe($rules);
        expect($result->context)->toBe(['key' => 'value']);
        expect($result->error)->toBe('Some error');
    });

    it('has default values', function () {
        $result = new EvaluationResult(passed: false);

        expect($result->passed)->toBe(false);
        expect($result->rules)->toBe([]);
        expect($result->context)->toBe([]);
        expect($result->error)->toBeNull();
    });

    it('returns passed status', function () {
        $passedResult = new EvaluationResult(passed: true);
        $failedResult = new EvaluationResult(passed: false);

        expect($passedResult->passed())->toBe(true);
        expect($failedResult->passed())->toBe(false);
    });

    it('returns failed rules', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => true],
            ['rule' => 'rule2', 'passed' => false],
            ['rule' => 'rule3', 'passed' => false],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);
        $failedRules = $result->failedRules();

        expect($failedRules)->toHaveCount(2);
        expect($failedRules[0]['rule'])->toBe('rule2');
        expect($failedRules[1]['rule'])->toBe('rule3');
    });

    it('returns rules with exceptions', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => true, 'error' => ''],
            ['rule' => 'rule2', 'passed' => false, 'error' => 'Some error'],
            ['rule' => 'rule3', 'passed' => false, 'error' => ''],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);
        $rulesWithExceptions = $result->rulesWithExceptions();

        expect($rulesWithExceptions)->toHaveCount(1);
        expect($rulesWithExceptions[0]['rule'])->toBe('rule2');
    });

    it('formats failed rules messages with errors', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => false, 'error' => 'Syntax error', 'error_message' => ''],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);

        $messages = $result->formatFailedRulesMessages();
        expect($messages)->toHaveCount(1);
        expect($messages[0])->toBe('Rule [rule1] failed due to error: Syntax error');
    });

    it('formats failed rules messages with custom message', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => false, 'error' => '', 'error_message' => 'Custom error'],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);

        $messages = $result->formatFailedRulesMessages();
        expect($messages[0])->toBe('Rule [rule1] failed: Custom error');
    });

    it('formats failed rules messages without message', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => false, 'error' => '', 'error_message' => ''],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);

        $messages = $result->formatFailedRulesMessages();
        expect($messages[0])->toBe('Rule [rule1] failed, but no error message provided.');
    });

    it('formats failed rules as string', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => false, 'error' => '', 'error_message' => 'Error 1'],
            ['rule' => 'rule2', 'passed' => false, 'error' => '', 'error_message' => 'Error 2'],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);

        $string = $result->formatFailedRulesAsString();
        expect($string)->toBe("Rule [rule1] failed: Error 1\nRule [rule2] failed: Error 2");
    });

    it('formats failed rules as string with custom separator', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => false, 'error' => '', 'error_message' => 'Error 1'],
            ['rule' => 'rule2', 'passed' => false, 'error' => '', 'error_message' => 'Error 2'],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);

        $string = $result->formatFailedRulesAsString(' | ');
        expect($string)->toBe("Rule [rule1] failed: Error 1 | Rule [rule2] failed: Error 2");
    });

    it('extracts actions from passed rules', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => true, 'action_type' => 'email', 'action_value' => ['to' => 'test@example.com']],
            ['rule' => 'rule2', 'passed' => false, 'action_type' => 'sms', 'action_value' => ['number' => '123']],
            ['rule' => 'rule3', 'passed' => true, 'action_type' => null, 'action_value' => null],
            ['rule' => 'rule4', 'passed' => true, 'action_type' => 'log', 'action_value' => ['level' => 'info']],
        ];

        $result = new EvaluationResult(passed: true, rules: $rules);
        $actions = $result->extractActionsFromResult();

        expect($actions)->toHaveCount(2);
        expect($actions[0])->toBe(['action_type' => 'email', 'action_value' => ['to' => 'test@example.com']]);
        expect($actions[1])->toBe(['action_type' => 'log', 'action_value' => ['level' => 'info']]);
    });

    it('returns empty array when no actions available', function () {
        $rules = [
            ['rule' => 'rule1', 'passed' => false, 'action_type' => 'email', 'action_value' => ['to' => 'test@example.com']],
            ['rule' => 'rule2', 'passed' => true, 'action_type' => null, 'action_value' => null],
        ];

        $result = new EvaluationResult(passed: false, rules: $rules);
        $actions = $result->extractActionsFromResult();

        expect($actions)->toHaveCount(0);
    });
});
