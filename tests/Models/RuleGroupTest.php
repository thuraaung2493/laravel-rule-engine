<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;

uses(RefreshDatabase::class);

describe('RuleGroup Model', function () {
    beforeEach(function () {
        $this->ruleGroup = RuleGroup::factory()->create([
            'name' => 'test_group',
            'evaluation_logic' => EvaluationLogic::ALL,
        ]);
    });

    it('has fillable attributes', function () {
        expect($this->ruleGroup->name)->toBe('test_group');
        expect($this->ruleGroup->evaluation_logic)->toBe(EvaluationLogic::ALL);
    });

    it('casts evaluation_logic to enum', function () {
        expect($this->ruleGroup->evaluation_logic)->toBeInstanceOf(EvaluationLogic::class);
    });

    it('has rules relationship', function () {
        $rule = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'active' => true,
            'priority' => 10,
        ]);

        expect($this->ruleGroup->rules)->toHaveCount(1);
        expect($this->ruleGroup->rules->first()->id)->toBe($rule->id);
    });

    it('only returns active rules ordered by priority desc', function () {
        Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'active' => false,
            'priority' => 20,
        ]);

        $activeRule1 = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'active' => true,
            'priority' => 10,
        ]);

        $activeRule2 = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'active' => true,
            'priority' => 30,
        ]);

        $rules = $this->ruleGroup->rules;
        expect($rules)->toHaveCount(2);
        expect($rules->first()->id)->toBe($activeRule2->id); // Higher priority first
        expect($rules->last()->id)->toBe($activeRule1->id);
    });
});
