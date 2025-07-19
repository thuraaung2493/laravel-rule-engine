<?php

use Thuraaung\RuleEngine\Dtos\EvaluationOptions;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;

describe('EvaluationOptions DTO', function () {
    it('creates from constructor', function () {
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

    it('has default values', function () {
        $options = new EvaluationOptions(
            groupNames: ['group1'],
            data: ['key' => 'value']
        );

        expect($options->logic)->toBe(EvaluationLogic::ALL);
        expect($options->sortByPriority)->toBe(false);
    });

    it('creates from array with array group names', function () {
        $options = EvaluationOptions::fromArray([
            'groupNames' => ['group1', 'group2'],
            'data' => ['key' => 'value'],
            'logic' => EvaluationLogic::ANY,
            'sortByPriority' => true,
        ]);

        expect($options->groupNames)->toBe(['group1', 'group2']);
        expect($options->data)->toBe(['key' => 'value']);
        expect($options->logic)->toBe(EvaluationLogic::ANY);
        expect($options->sortByPriority)->toBe(true);
    });

    it('creates from array with string group name', function () {
        $options = EvaluationOptions::fromArray([
            'groupNames' => 'single_group',
            'data' => ['key' => 'value'],
        ]);

        expect($options->groupNames)->toBe(['single_group']);
        expect($options->logic)->toBe(EvaluationLogic::ALL);
        expect($options->sortByPriority)->toBe(false);
    });

    it('uses defaults when keys missing in array', function () {
        $options = EvaluationOptions::fromArray([
            'groupNames' => ['group1'],
            'data' => ['key' => 'value'],
        ]);

        expect($options->logic)->toBe(EvaluationLogic::ALL);
        expect($options->sortByPriority)->toBe(false);
    });
});
