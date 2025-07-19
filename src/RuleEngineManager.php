<?php

namespace Thuraaung\RuleEngine;

use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Dtos\EvaluationOptions;
use Thuraaung\RuleEngine\Dtos\EvaluationResult;
use Thuraaung\RuleEngine\Dtos\MultiGroupEvaluationResult;

class RuleEngineManager
{
    public function __construct(
        protected RuleEngine $engine,
        protected RuleEngineRegistrar $registrar
    ) {}

    public function register(string $type, string|ActionHandlerInterface $handler): void
    {
        $this->registrar->register($type, $handler);
    }

    public function evaluateGroup(EvaluationOptions $options): EvaluationResult
    {
        return $this->engine->evaluateGroup($options);
    }

    public function evaluateGroups(EvaluationOptions $options): MultiGroupEvaluationResult
    {
        return $this->engine->evaluateGroups($options);
    }
}
