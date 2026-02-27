<?php

use Thuraaung\RuleEngine\Dtos\EvaluationOptions;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;

describe('EvaluationOptions DTO', function (): void {
    it('creates from constructor', function (): void {
        $options = new EvaluationOptions(
            groupNames: ['group1', 'group2'],
            data: ['key' => 'value'],
            logic: EvaluationLogic::ANY,
            sortByPriority: true
        );

        expect($options->groupNames)->toBe(['group1', 'group2']);
        expect($options->data)->toBe(['key' => 'value']);
        expect($options->logic)->toBe(EvaluationLogic::ANY);
        expect($options->sortByPriority)->toBe(true);
    });

    it('has default values', function (): void {
        $options = new EvaluationOptions(
            groupNames: ['group1'],
            data: ['key' => 'value']
        );

        expect($options->logic)->toBe(EvaluationLogic::ALL);
        expect($options->sortByPriority)->toBe(false);
    });

    it('creates from array with array group names', function (): void {
        $evaluationOptions = EvaluationOptions::fromArray([
            'groupNames' => ['group1', 'group2'],
            'data' => ['key' => 'value'],
            'logic' => EvaluationLogic::ANY,
            'sortByPriority' => true,
        ]);

        expect($evaluationOptions->groupNames)->toBe(['group1', 'group2']);
        expect($evaluationOptions->data)->toBe(['key' => 'value']);
        expect($evaluationOptions->logic)->toBe(EvaluationLogic::ANY);
        expect($evaluationOptions->sortByPriority)->toBe(true);
    });

    it('creates from array with string group name', function (): void {
        $evaluationOptions = EvaluationOptions::fromArray([
            'groupNames' => 'single_group',
            'data' => ['key' => 'value'],
        ]);

        expect($evaluationOptions->groupNames)->toBe(['single_group']);
        expect($evaluationOptions->logic)->toBe(EvaluationLogic::ALL);
        expect($evaluationOptions->sortByPriority)->toBe(false);
    });

    it('uses defaults when keys missing in array', function (): void {
        $evaluationOptions = EvaluationOptions::fromArray([
            'groupNames' => ['group1'],
            'data' => ['key' => 'value'],
        ]);

        expect($evaluationOptions->logic)->toBe(EvaluationLogic::ALL);
        expect($evaluationOptions->sortByPriority)->toBe(false);
    });
});
