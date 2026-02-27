<?php

use Thuraaung\RuleEngine\ExpressionProviders\PhpFunctionsProvider;

/**
 * Default configuration for the rule engine.
 * This file can be published to config/rule-engine.php using the command:
 * php artisan vendor:publish --provider="Thuraaung\RuleEngine\RuleEngineServiceProvider"
 */
return [

    /**
     * Whether to throw exceptions when a action handler is not found.
     */
    'throw_on_error' => false,

    /**
     * Custom action handlers can be registered here.
     * The key is the action type, and the value is the handler class.
     * Handlers should implement \Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface.
     */
    'custom_handlers' => [
        // 'action_type' => \App\RuleEngine\Actions\YourHandler::class,
    ],

    /**
     * Expression providers to be registered with the evaluator.
     * These can provide custom functions for use in rule expressions.
     */
    'expression_providers' => [
        // Default PHP functions provider
        PhpFunctionsProvider::class,

        // Add other expression providers here
    ],
];
