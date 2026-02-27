<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Thuraaung\RuleEngine\Models\Rule;
use Thuraaung\RuleEngine\Models\RuleGroup;

uses(RefreshDatabase::class);

describe('Rule Model', function (): void {
    beforeEach(function (): void {
        $this->ruleGroup = RuleGroup::factory()->create();
        $this->rule = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'action_value' => ['key' => 'value'],
            'active' => true,
        ]);
    });

    it('has fillable attributes', function (): void {
        expect($this->rule->rule_group_id)->toBe($this->ruleGroup->id);
        expect($this->rule->active)->toBe(true);
    });

    it('casts action_value to array', function (): void {
        expect($this->rule->action_value)->toBe(['key' => 'value']);
    });

    it('casts active to boolean', function (): void {
        expect($this->rule->active)->toBeTrue();
    });

    it('belongs to rule group', function (): void {
        expect($this->rule->ruleGroup->id)->toBe($this->ruleGroup->id);
    });

    it('handles null action_value', function (): void {
        $rule = Rule::factory()->create([
            'rule_group_id' => $this->ruleGroup->id,
            'action_value' => null,
        ]);

        expect($rule->action_value)->toBeNull();
    });
});
