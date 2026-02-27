<?php

use Thuraaung\RuleEngine\Enums\EvaluationLogic;

describe('EvaluationLogic Enum', function (): void {
    it('has correct values', function (): void {
        expect(EvaluationLogic::ALL->value)->toBe('all');
        expect(EvaluationLogic::ANY->value)->toBe('any');
        expect(EvaluationLogic::FAIL_FAST->value)->toBe('fail_fast');
    });

    it('returns correct values array', function (): void {
        $values = EvaluationLogic::values();
        expect($values)->toBe(['all', 'any', 'fail_fast']);
    });

    it('returns correct options array', function (): void {
        $options = EvaluationLogic::options();
        expect($options)->toBe([
            'all' => 'All rules must pass',
            'any' => 'Any rule must pass',
            'fail_fast' => 'Fail as soon as any rule fails',
        ]);
    });

    it('can check if a logic is of a specific type', function (): void {
        expect(EvaluationLogic::ALL->is(EvaluationLogic::ALL))->toBeTrue();
        expect(EvaluationLogic::ANY->is(EvaluationLogic::ANY))->toBeTrue();
        expect(EvaluationLogic::FAIL_FAST->is(EvaluationLogic::FAIL_FAST))->toBeTrue();

        expect(EvaluationLogic::ALL->is('all'))->toBeTrue();
        expect(EvaluationLogic::ANY->is('any'))->toBeTrue();
        expect(EvaluationLogic::FAIL_FAST->is('fail_fast'))->toBeTrue();

        expect(EvaluationLogic::ALL->is(EvaluationLogic::ANY))->toBeFalse();
        expect(EvaluationLogic::ALL->is(EvaluationLogic::FAIL_FAST))->toBeFalse();
        expect(EvaluationLogic::ANY->is(EvaluationLogic::ALL))->toBeFalse();
    });
});
