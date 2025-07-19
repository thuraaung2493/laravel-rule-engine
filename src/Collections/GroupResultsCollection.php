<?php

namespace Thuraaung\RuleEngine\Collections;

use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Illuminate\Support\Collection;
use Thuraaung\RuleEngine\Dtos\EvaluationResult;
use Thuraaung\RuleEngine\Dtos\MultiGroupEvaluationResult;

class GroupResultsCollection extends Collection
{
    protected Collection $context;

    public function __construct($items = [])
    {
        parent::__construct($items);
        $this->context = collect();
    }

    public function addResult(string $groupName, EvaluationResult $result): self
    {
        $this->put($groupName, $result);

        if ($result->passed()) {
            $this->context = $this->context->merge($result->context ?? collect());
        }

        return $this;
    }

    public function toMultiGroupResult(EvaluationLogic $logic = EvaluationLogic::ALL): MultiGroupEvaluationResult
    {
        $passed = match ($logic) {
            EvaluationLogic::ALL => $this->every(fn($r) => $r->passed()),
            EvaluationLogic::ANY => $this->contains(fn($r) => $r->passed()),
            EvaluationLogic::FAIL_FAST => $this->every(fn($r) => $r->passed()),
            default => false,
        };

        return new MultiGroupEvaluationResult(
            passed: $passed,
            groupResults: collect($this->all()),
            context: $this->context
        );
    }

    public function failFast(string $groupName): MultiGroupEvaluationResult
    {
        return new MultiGroupEvaluationResult(
            passed: false,
            groupResults: collect($this->all()),
            context: $this->context,
            error: __("rule-engine::messages.rule_group_failed", ['group' => $groupName])
        );
    }

    public function passAny(): MultiGroupEvaluationResult
    {
        return new MultiGroupEvaluationResult(
            passed: true,
            groupResults: collect($this->all()),
            context: $this->context
        );
    }
}
