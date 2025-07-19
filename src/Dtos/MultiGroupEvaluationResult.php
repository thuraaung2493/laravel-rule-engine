<?php

namespace Thuraaung\RuleEngine\Dtos;

use Illuminate\Support\Collection;

class MultiGroupEvaluationResult
{
    public function __construct(
        public bool $passed,
        public ?Collection $groupResults = null,
        public ?Collection $context = null,
        public ?string $error = null
    ) {
        $this->groupResults = $groupResults ?? collect();
        $this->context = $context ?? collect();
    }

    public function failedGroups(): Collection
    {
        return $this->groupResults
            ->filter(fn(EvaluationResult $result) => !$result->passed())
            ->keys()
            ->values();
    }

    public function hasFailedGroups(): bool
    {
        return $this->failedGroups()->isNotEmpty();
    }
}
