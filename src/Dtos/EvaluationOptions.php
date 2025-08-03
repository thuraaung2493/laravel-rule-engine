<?php

namespace Thuraaung\RuleEngine\Dtos;

use Thuraaung\RuleEngine\Enums\EvaluationLogic;

class EvaluationOptions
{
    /**
     * Create EvaluationOptions
     *
     * @param  array<string>  $groupNames
     */
    public function __construct(
        public readonly array $groupNames,
        public readonly array $data,
        public readonly EvaluationLogic $logic = EvaluationLogic::ALL,
        public readonly bool $sortByPriority = false,
    ) {}

    /**
     * Create EvaluationOptions from array
     *
     * @param  array{groupNames: array|string, data: array, logic?: EvaluationLogic, sortByPriority?: bool}  $options
     */
    public static function fromArray(array $options): self
    {
        // Ensure groupNames is always an array, even if a single string is passed
        $groupNames = is_array($options['groupNames'])
            ? $options['groupNames']
            : [$options['groupNames']];

        return new self(
            groupNames: $groupNames,
            data: $options['data'],
            logic: $options['logic'] ?? EvaluationLogic::ALL,
            sortByPriority: $options['sortByPriority'] ?? false,
        );
    }
}
