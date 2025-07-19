<?php

namespace Thuraaung\RuleEngine;

use Thuraaung\RuleEngine\Enums\EvaluationLogic;
use Illuminate\Database\Eloquent\Collection;
use Thuraaung\RuleEngine\Actions\RuleActionHandler;
use Thuraaung\RuleEngine\Collections\GroupResultsCollection;
use Thuraaung\RuleEngine\Dtos\EvaluationOptions;
use Thuraaung\RuleEngine\Dtos\EvaluationResult;
use Thuraaung\RuleEngine\Dtos\MultiGroupEvaluationResult;
use Thuraaung\RuleEngine\Models\RuleGroup;
use Illuminate\Support\Collection as BaseCollection;

class RuleEngine
{
    public function __construct(
        protected RuleEvaluator $evaluator,
        protected RuleActionHandler $actionHandler
    ) {}

    public function evaluateGroup(EvaluationOptions $options): EvaluationResult
    {
        $groupNames = collect($options->groupNames);

        if ($groupNames->count() !== 1) {
            return new EvaluationResult(
                passed: false,
                error: "evaluateGroup expects exactly one groupName in options."
            );
        }

        $groupName = $groupNames->first();

        $group = $this->getRuleGroup($groupName);

        if (!$group) {
            return new EvaluationResult(
                passed: false,
                error: "Rule group '{$groupName}' not found."
            );
        }

        try {
            $evaluatedRules = $this->evaluator->evaluateRules($group, $options->data);

            $context = $this->dispatchActions($evaluatedRules);
            $passed = $this->determineGroupResult($group->evaluation_logic, $evaluatedRules);
        } catch (\Exception $e) {
            return new EvaluationResult(
                passed: false,
                error: $e->getMessage()
            );
        }

        return new EvaluationResult(
            passed: $passed,
            rules: $evaluatedRules,
            context: $context
        );
    }

    public function evaluateGroups(EvaluationOptions $options): MultiGroupEvaluationResult
    {
        $groups = $this->getRuleGroups($options->groupNames, $options->sortByPriority);
        $results = new GroupResultsCollection();

        foreach ($groups as $group) {
            $singleGroupOptions = new EvaluationOptions(
                groupNames: [$group->name],
                data: $options->data,
                logic: $group->evaluation_logic
            );
            $result = $this->evaluateGroup($singleGroupOptions);
            $results->addResult($group->name, $result);

            if ($options->logic === EvaluationLogic::FAIL_FAST && !$result->passed()) {
                return $results->failFast($group->name);
            }

            if ($options->logic === EvaluationLogic::ANY && $result->passed()) {
                return $results->passAny();
            }
        }

        return $results->toMultiGroupResult($options->logic);
    }

    protected function dispatchActions(BaseCollection $evaluatedRules): BaseCollection
    {
        return $evaluatedRules
            ->filter(fn($rule) => $rule['passed'] && $rule['action_type'])
            ->reduce(function (BaseCollection $context, $rule) {
                return $this->actionHandler->handle(
                    $rule['action_type'],
                    $rule['action_value'] ?? [],
                    $context
                );
            }, collect());
    }

    protected function getRuleGroups(array $names, bool $sortByPriority = false): Collection
    {
        $groups = RuleGroup::query()
            ->with('rules')
            ->whereIn('name', $names)
            ->get();

        return $sortByPriority
            ? $groups->sortByDesc('priority')->values()
            : $groups;
    }

    protected function getRuleGroup(string $name): ?RuleGroup
    {
        return RuleGroup::query()
            ->with('rules')
            ->where('name', $name)
            ->first();
    }

    protected function determineGroupResult(EvaluationLogic $logic, BaseCollection $evaluatedRules): bool
    {
        return match ($logic) {
            EvaluationLogic::ALL => $evaluatedRules->every(fn($r) => $r['passed']),
            EvaluationLogic::ANY => $evaluatedRules->contains(fn($r) => $r['passed']),
            EvaluationLogic::FAIL_FAST => $evaluatedRules->every(fn($r) => $r['passed']),
            default => false,
        };
    }
}
