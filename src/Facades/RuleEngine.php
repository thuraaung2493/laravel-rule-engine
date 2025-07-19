<?php

namespace Thuraaung\RuleEngine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Thuraaung\RuleEngine\RuleEngineManager register(string $type, string|\Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface $handler)
 * @method static \Thuraaung\RuleEngine\RuleEngineManager evaluateGroup(\Thuraaung\RuleEngine\EvaluationOptions $options)
 * @method static \Thuraaung\RuleEngine\RuleEngineManager evaluateGroups(\Thuraaung\RuleEngine\EvaluationOptions $options)
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
