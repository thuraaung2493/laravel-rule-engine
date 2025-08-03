<?php

use Thuraaung\RuleEngine\Actions\RuleActionHandler;
use Thuraaung\RuleEngine\Dtos\EvaluationOptions;
use Thuraaung\RuleEngine\Dtos\RuleResult;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Thuraaung\RuleEngine\RuleEngine;
use Thuraaung\RuleEngine\RuleEvaluator;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

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

        $rule = Rule::factory()->create([
            'rule_group_id' => $group->id,
            'name' => 'test_rule',
            'active' => true,
            'action_type' => 'log',
            'action_value' => ['level' => 'info'],
        ]);

        $evaluatedRules = collect([RuleResult::create(true, $rule, null)]);

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
        expect($result->error)->toBe('evaluateGroup expects exactly one groupName in options.');
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

        $rule1 = Rule::factory()->active()->noAction()->create(['rule_group_id' => $group->id]);
        $rule2 = Rule::factory()->active()->noAction()->create(['rule_group_id' => $group->id]);

        $evaluatedRules = collect([
            RuleResult::create(false, $rule1, null),
            RuleResult::create(true, $rule2, null),
        ]);

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

        $rule1 = Rule::factory()->active()->noAction()->create(['rule_group_id' => $group->id]);
        $rule2 = Rule::factory()->active()->noAction()->create(['rule_group_id' => $group->id]);

        $evaluatedRules = collect([
            RuleResult::create(false, $rule1, null),
            RuleResult::create(true, $rule2, null),
        ]);

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

        $rule1 = Rule::factory()->active()->noAction()->make(['rule_group_id' => $group->id]);
        $rule2 = Rule::factory()->active()->noAction()->make(['rule_group_id' => $group->id]);

        $evaluatedRules = collect([
            RuleResult::create(false, $rule1, null),
            RuleResult::create(false, $rule2, null),
        ]);

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

        $rule1 = Rule::factory()->active()->noAction()->make(['rule_group_id' => $group->id]);
        $rule2 = Rule::factory()->active()->noAction()->make(['rule_group_id' => $group->id]);

        $evaluatedRules = collect([
            RuleResult::create(true, $rule1, null),
            RuleResult::create(true, $rule2, null),
        ]);

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

        $rule1 = Rule::factory()->active()->withAction('email', ['to' => 'test@example.com'])->for($group)->create();
        $rule2 = Rule::factory()->active()->withAction('sms', ['number' => '123'])->for($group)->create();
        $rule3 = Rule::factory()->active()->noAction()->for($group)->create();

        $evaluatedRules = collect([
            RuleResult::create(true, $rule1, null),
            RuleResult::create(false, $rule2, null),
            RuleResult::create(true, $rule3, null),
        ]);

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

        $rule1 = Rule::factory()->active()->noAction()->for($group1)->create();
        $rule2 = Rule::factory()->active()->noAction()->for($group2)->create();

        $evaluatedRules1 = collect([RuleResult::create(true, $rule1)]);
        $evaluatedRules2 = collect([RuleResult::create(true, $rule2)]);

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

        $rule1 = Rule::factory()->active()->noAction()->for($group2)->make();

        $evaluatedRules1 = collect([RuleResult::create(false, $rule1)]);

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
        expect($result->groupResults)->toHaveCount(1);
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

        $rule1 = Rule::factory()->active()->noAction()->for($group2)->make();

        $evaluatedRules1 = collect([RuleResult::create(true, $rule1)]);

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
        expect($result->groupResults)->toHaveCount(1);
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

        $evaluatedRules = collect();

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
        expect($result->rules)->toHaveCount(0);
    });

    it('handles evaluation with empty data', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $rule1 = Rule::factory()->active()->noAction([])->for($group)->create();

        $evaluatedRules = collect([RuleResult::create(true, $rule1)]);

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

        $this->actionHandler->shouldReceive('handle')->never();

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

        $rule1 = Rule::factory()->active()->noAction()->for($group1)->create();
        $rule2 = Rule::factory()->active()->noAction()->for($group2)->create();

        $evaluatedRules1 = collect([RuleResult::create(true, $rule1)]);
        $evaluatedRules2 = collect([RuleResult::create(false, $rule2)]);

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

    it('handles multiple actions for a single rule group', function () {
        $group = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);

        $rule1 = Rule::factory()->active()->create([
            'rule_group_id' => $group->id,
            'action_type' => 'email',
            'action_value' => ['to' => 'test@example.com'],
        ]);

        $rule2 = Rule::factory()->active()->create([
            'rule_group_id' => $group->id,
            'action_type' => 'sms',
            'action_value' => ['number' => '123456'],
        ]);

        $evaluatedRules = collect([
            RuleResult::create(true, $rule1, null),
            RuleResult::create(true, $rule2, null),
        ]);

        $this->evaluator->shouldReceive('evaluateRules')
            ->once()
            ->andReturn($evaluatedRules);

        $this->actionHandler->shouldReceive('handle')
            ->twice()
            ->withArgs(function ($type, $value) {
                return ($type === 'email' && $value === ['to' => 'test@example.com']) ||
                    ($type === 'sms' && $value === ['number' => '123456']);
            });

        $options = new EvaluationOptions(
            groupNames: ['test_group'],
            data: ['test' => 'data']
        );

        $result = $this->engine->evaluateGroup($options);
        expect($result->passed())->toBe(true);
    });
});
