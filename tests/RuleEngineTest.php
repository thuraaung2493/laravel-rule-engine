<?php

use Thuraaung\RuleEngine\Actions\RuleActionHandler;
use Thuraaung\RuleEngine\RuleEngine;
use Thuraaung\RuleEngine\RuleEvaluator;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Dtos\EvaluationOptions;

describe('RuleEngine', function () {
    beforeEach(function () {
        $this->evaluator = Mockery::mock(RuleEvaluator::class);
        $this->actionHandler = Mockery::mock(RuleActionHandler::class);
        $this->engine = new RuleEngine($this->evaluator, $this->actionHandler);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('evaluates single group successfully', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        Rule::factory()->create([
            'rule_group_id' => $group->id,
            'name' => 'test_rule',
            'active' => true,
        ]);

        $evaluatedRules = [
            [
                'rule' => 'test_rule',
                'passed' => true,
                'action_type' => 'log',
                'action_value' => ['level' => 'info'],
            ],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->withArgs(function (RuleGroup $receivedGroup, array $data) use ($group) {
                return $receivedGroup->id === $group->id && $data === ['test' => 'data'];
            })
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')
            ->once()
            ->with('log', ['level' => 'info'], Mockery::any());

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);

        expect($result->passed())->toBe(true);
        expect($result->rules)->toBe($evaluatedRules);
    });

    it('fails when multiple group names provided to evaluateGroup', function () {
        $options = new EvaluationOptions(
            groupNames: ['group1', 'group2'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);

        expect($result->passed())->toBe(false);
        expect($result->error)->toBe("evaluateGroup expects exactly one groupName in options.");
    });

    it('fails when group not found', function () {
        $options = new EvaluationOptions(
            groupNames: ['nonexistent_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);

        expect($result->passed())->toBe(false);
        expect($result->error)->toBe("Rule group 'nonexistent_group' not found.");
    });

    it('determines group result with ALL logic fails when one rule fails', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $evaluatedRules = [
            ['rule' => 'rule1', 'passed' => true, 'action_type' => null],
            ['rule' => 'rule2', 'passed' => false, 'action_type' => null],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(false);
    });

    it('determines group result with ANY logic passes when one rule passes', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ANY,
        ]);

        $evaluatedRules = [
            ['rule' => 'rule1', 'passed' => false, 'action_type' => null],
            ['rule' => 'rule2', 'passed' => true, 'action_type' => null],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(true);
    });

    it('determines group result with ANY logic fails when all rules fail', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ANY,
        ]);

        $evaluatedRules = [
            ['rule' => 'rule1', 'passed' => false, 'action_type' => null],
            ['rule' => 'rule2', 'passed' => false, 'action_type' => null],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(false);
    });

    it('determines group result with FAIL_FAST logic', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::FAIL_FAST,
        ]);

        $evaluatedRules = [
            ['rule' => 'rule1', 'passed' => true, 'action_type' => null],
            ['rule' => 'rule2', 'passed' => true, 'action_type' => null],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(true);
    });

    it('dispatches actions for passed rules', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $evaluatedRules = [
            [
                'rule' => 'rule1',
                'passed' => true,
                'action_type' => 'email',
                'action_value' => ['to' => 'test@example.com'],
            ],
            [
                'rule' => 'rule2',
                'passed' => false,
                'action_type' => 'sms',
                'action_value' => ['number' => '123'],
            ],
            [
                'rule' => 'rule3',
                'passed' => true,
                'action_type' => null,
                'action_value' => null,
            ],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')
            ->once()
            ->with('email', ['to' => 'test@example.com'], Mockery::any());

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $this->engine->evaluateGroup($options);
    });

    it('evaluates multiple groups successfully', function () {
        $group1 = RuleGroup::factory()->create([
            'name' => 'group1',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 10,
        ]);

        $group2 = RuleGroup::factory()->create([
            'name' => 'group2',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 20,
        ]);

        $evaluatedRules1 = [['rule' => 'rule1', 'passed' => true, 'action_type' => null]];
        $evaluatedRules2 = [['rule' => 'rule2', 'passed' => true, 'action_type' => null]];

        $this->evaluator->shouldReceive('evaluateRules')
            ->twice()
            ->andReturn($evaluatedRules1, $evaluatedRules2);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['group1', 'group2'],
            data: ['test' => 'data'],
            logic: EvaluationLogic::ALL,
            sortByPriority: true
        );

        $result = $this->engine->evaluateGroups($options);

        expect($result->passed)->toBe(true);
        expect($result->groupResults)->toHaveCount(2);
        expect($result->groupResults)->toHaveKey('group1');
        expect($result->groupResults)->toHaveKey('group2');
    });

    it('evaluates multiple groups with FAIL_FAST logic', function () {
        $group1 = RuleGroup::factory()->create([
            'name' => 'group1',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 10,
        ]);

        $group2 = RuleGroup::factory()->create([
            'name' => 'group2',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 20,
        ]);

        $evaluatedRules1 = [['rule' => 'rule1', 'passed' => false, 'action_type' => null]];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules1);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['group1', 'group2'],
            data: ['test' => 'data'],
            logic: EvaluationLogic::FAIL_FAST,
            sortByPriority: true
        );

        $result = $this->engine->evaluateGroups($options);

        expect($result->passed)->toBe(false);
        expect($result->groupResults)->toHaveCount(1); // Only first group evaluated
        expect($result->groupResults)->toHaveKey('group2');
    });

    it('evaluates multiple groups with ANY logic', function () {
        $group1 = RuleGroup::factory()->create([
            'name' => 'group1',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 10,
        ]);

        $group2 = RuleGroup::factory()->create([
            'name' => 'group2',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 20,
        ]);

        $evaluatedRules1 = [['rule' => 'rule1', 'passed' => true, 'action_type' => null]];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules1);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['group1', 'group2'],
            data: ['test' => 'data'],
            logic: EvaluationLogic::ANY,
            sortByPriority: true
        );

        $result = $this->engine->evaluateGroups($options);

        expect($result->passed)->toBe(true);
        expect($result->groupResults)->toHaveCount(1); // Only first group evaluated
        expect($result->groupResults)->toHaveKey('group2');
    });

    it('handles empty group names', function () {
        $options = new EvaluationOptions(
            groupNames: [],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroups($options);

        expect($result->passed)->toBe(true);
        expect($result->groupResults)->toHaveCount(0);
    });

    it('handles nonexistent groups in evaluateGroups', function () {
        $options = new EvaluationOptions(
            groupNames: ['nonexistent_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroups($options);

        expect($result->passed)->toBe(true); // No groups to evaluate
        expect($result->groupResults)->toHaveCount(0);
    });

    it('handles group with no rules', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'empty_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $evaluatedRules = [];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')->never();

        $options = new EvaluationOptions(
            groupNames: ['empty_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(true);
        expect($result->rules)->toBe([]);
    });

    it('handles evaluation with empty data', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $evaluatedRules = [
            ['rule' => 'rule1', 'passed' => true, 'action_type' => null],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->withArgs(function (RuleGroup $receivedGroup, array $data) use ($group) {
                return $receivedGroup->id === $group->id && empty($data);
            })
            ->andReturn($evaluatedRules);

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: []
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(true);
    });

    it('handles rule evaluation exception', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andThrow(new \RuntimeException('Expression evaluation failed'));

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(false);
        expect($result->error)->toBe('Expression evaluation failed');
    });

    it('handles mixed success and failure in multiple groups', function () {
        $group1 = RuleGroup::factory()->create([
            'name' => 'group1',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 10,
        ]);

        $group2 = RuleGroup::factory()->create([
            'name' => 'group2',
            'evaluation_logic' => EvaluationLogic::ALL,
            'priority' => 20,
        ]);

        $evaluatedRules1 = [['rule' => 'rule1', 'passed' => true, 'action_type' => null]];
        $evaluatedRules2 = [['rule' => 'rule2', 'passed' => false, 'action_type' => null]];

        $this->evaluator->shouldReceive('evaluateRules')
            ->twice()
            ->andReturn($evaluatedRules1, $evaluatedRules2);

        $options = new EvaluationOptions(
            groupNames: ['group1', 'group2'],
            data: ['test' => 'data'],
            logic: EvaluationLogic::ALL,
            sortByPriority: true
        );

        $result = $this->engine->evaluateGroups($options);
        expect($result->passed)->toBe(false);
        expect($result->groupResults)->toHaveCount(2);
    });

    it('handles multiple actions for a single rule', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $evaluatedRules = [
            [
                'rule' => 'rule1',
                'passed' => true,
                'action_type' => 'email',
                'action_value' => ['to' => 'test@example.com'],
            ],
            [
                'rule' => 'rule1',
                'passed' => true,
                'action_type' => 'sms',
                'action_value' => ['number' => '123456'],
            ],
        ];

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')
            ->twice()
            ->withArgs(function ($type, $value) {
                return (($type === 'email' && $value === ['to' => 'test@example.com']) ||
                    ($type === 'sms' && $value === ['number' => '123456']));
            });

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(true);
    });
});
