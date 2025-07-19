<?php

use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Rule Model', function () {
    beforeEach(function () {
        $this->ruleGroup = RuleGroup::factory()->create();
        $this->rule = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'action_value' => ['key' => 'value'],
            'active' => true,
        ]);
    });

    it('has fillable attributes', function () {
        expect($this->rule->rule_group_id)->toBe($this->ruleGroup->id);
        expect($this->rule->active)->toBe(true);
    });

    it('casts action_value to array', function () {
        expect($this->rule->action_value)->toBe(['key' => 'value']);
    });

    it('casts active to boolean', function () {
        expect($this->rule->active)->toBeTrue();
    });

    it('belongs to rule group', function () {
        expect($this->rule->ruleGroup->id)->toBe($this->ruleGroup->id);
    });

    it('handles null action_value', function () {
        $rule = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'action_value' => null,
        ]);

        expect($rule->action_value)->toBeNull();
    });
});
