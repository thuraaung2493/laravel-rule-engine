<?php

use Thuraaung\RuleEngine\Enums\EvaluationLogic;

describe('EvaluationLogic Enum', function () {
    it('has correct values', function () {
        expect(EvaluationLogic::ALL->value)->toBe('all');
        expect(EvaluationLogic::ANY->value)->toBe('any');
        expect(EvaluationLogic::FAIL_FAST->value)->toBe('fail_fast');
    });

    it('returns correct values array', function () {
        $values = EvaluationLogic::values();
        expect($values)->toBe(['all', 'any', 'fail_fast']);
    });

    it('returns correct options array', function () {
        $options = EvaluationLogic::options();
        expect($options)->toBe([
            'all' => 'All rules must pass',
            'any' => 'Any rule must pass',
            'fail_fast' => 'Fail as soon as any rule fails',
        ]);
    });
});
