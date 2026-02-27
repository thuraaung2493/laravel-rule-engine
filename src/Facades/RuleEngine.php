<?php

namespace Thuraaung\RuleEngine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(string $type, string|\Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface $handler)
 * @method static \Thuraaung\RuleEngine\Dtos\EvaluationResult evaluateGroup(\Thuraaung\RuleEngine\EvaluationOptions $options)
 * @method static \Thuraaung\RuleEngine\Dtos\MultiGroupEvaluationResult evaluateGroups(\Thuraaung\RuleEngine\EvaluationOptions $options)
 *
 * @see \Thuraaung\RuleEngine\RuleEngineManager
 */
class RuleEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Thuraaung\RuleEngine\RuleEngineManager::class;
    }
}
