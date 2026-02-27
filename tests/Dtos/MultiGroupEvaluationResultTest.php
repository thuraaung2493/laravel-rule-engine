<?php

use Thuraaung\RuleEngine\Dtos\EvaluationResult;
use Thuraaung\RuleEngine\Dtos\MultiGroupEvaluationResult;

describe('MultiGroupEvaluationResult DTO', function (): void {
    it('creates with all parameters', function (): void {
        $groupResults = collect([
            'group1' => new EvaluationResult(passed: true),
            'group2' => new EvaluationResult(passed: false),
        ]);

        $result = new MultiGroupEvaluationResult(
            passed: false,
            groupResults: $groupResults,
            context: collect(['key' => 'value']),
            error: 'Some error'
        );

        expect($result->passed)->toBe(false);
        expect($result->groupResults->toArray())->toBe($groupResults->toArray());
        expect($result->context->toArray())->toBe(['key' => 'value']);
        expect($result->error)->toBe('Some error');
    });

    it('has default values', function (): void {
        $result = new MultiGroupEvaluationResult(
            passed: true,
            groupResults: collect([])
        );

        expect($result->passed)->toBe(true);
        expect($result->groupResults->toArray())->toBe([]);
        expect($result->context->toArray())->toBe([]);
        expect($result->error)->toBeNull();
    });

    it('returns failed groups', function (): void {
        $groupResults = collect([
            'group1' => new EvaluationResult(passed: true),
            'group2' => new EvaluationResult(passed: false),
            'group3' => new EvaluationResult(passed: false),
        ]);

        $result = new MultiGroupEvaluationResult(
            passed: false,
            groupResults: $groupResults
        );

        $failedGroups = $result->failedGroups();
        expect($failedGroups->toArray())->toBe(['group2', 'group3']);
    });

    it('returns empty when no failed groups', function (): void {
        $groupResults = [
            'group1' => new EvaluationResult(passed: true),
            'group2' => new EvaluationResult(passed: true),
        ];

        $result = new MultiGroupEvaluationResult(
            passed: true,
            groupResults: collect($groupResults)
        );

        expect($result->failedGroups()->isEmpty())->toBeTrue();
    });

    it('checks if has failed groups', function (): void {
        $passedResult = new MultiGroupEvaluationResult(
            passed: true,
            groupResults: collect([
                'group1' => new EvaluationResult(passed: true),
            ])
        );

        $failedResult = new MultiGroupEvaluationResult(
            passed: false,
            groupResults: collect([
                'group1' => new EvaluationResult(passed: false),
            ])
        );

        expect($passedResult->hasFailedGroups())->toBe(false);
        expect($failedResult->hasFailedGroups())->toBe(true);
    });
});
