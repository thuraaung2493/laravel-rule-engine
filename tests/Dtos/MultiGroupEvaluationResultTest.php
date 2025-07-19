<?php

use Thuraaung\RuleEngine\Dtos\MultiGroupEvaluationResult;
use Thuraaung\RuleEngine\Dtos\EvaluationResult;

describe('MultiGroupEvaluationResult DTO', function () {
    it('creates with all parameters', function () {
        $groupResults = [
            'group1' => new EvaluationResult(passed: true),
            'group2' => new EvaluationResult(passed: false),
        ];

        $result = new MultiGroupEvaluationResult(
            passed: false,
            groupResults: $groupResults,
            context: ['key' => 'value'],
            error: 'Some error'
        );

        expect($result->passed)->toBe(false);
        expect($result->groupResults)->toBe($groupResults);
        expect($result->context)->toBe(['key' => 'value']);
        expect($result->error)->toBe('Some error');
    });

    it('has default values', function () {
        $result = new MultiGroupEvaluationResult(
            passed: true,
            groupResults: []
        );

        expect($result->passed)->toBe(true);
        expect($result->groupResults)->toBe([]);
        expect($result->context)->toBe([]);
        expect($result->error)->toBeNull();
    });

    it('returns failed groups', function () {
        $groupResults = [
            'group1' => new EvaluationResult(passed: true),
            'group2' => new EvaluationResult(passed: false),
            'group3' => new EvaluationResult(passed: false),
        ];

        $result = new MultiGroupEvaluationResult(
            passed: false,
            groupResults: $groupResults
        );

        $failedGroups = $result->failedGroups();
        expect($failedGroups)->toBe(['group2', 'group3']);
    });

    it('returns empty array when no failed groups', function () {
        $groupResults = [
            'group1' => new EvaluationResult(passed: true),
            'group2' => new EvaluationResult(passed: true),
        ];

        $result = new MultiGroupEvaluationResult(
            passed: true,
            groupResults: $groupResults
        );

        expect($result->failedGroups())->toBe([]);
    });

    it('checks if has failed groups', function () {
        $passedResult = new MultiGroupEvaluationResult(
            passed: true,
            groupResults: [
                'group1' => new EvaluationResult(passed: true),
            ]
        );

        $failedResult = new MultiGroupEvaluationResult(
            passed: false,
            groupResults: [
                'group1' => new EvaluationResult(passed: false),
            ]
        );

        expect($passedResult->hasFailedGroups())->toBe(false);
        expect($failedResult->hasFailedGroups())->toBe(true);
    });
});
