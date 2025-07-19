<?php

namespace Thuraaung\RuleEngine;

use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;

class RuleEngineRegistrar
{
    public function __construct(
        protected ActionRegistry $registry
    ) {}

    public function register(string $type, ActionHandlerInterface|string $handler): void
    {
        if (is_string($handler)) {
            $handler = app($handler);
        }

        $this->registry->register($type, $handler);
    }

    public function registerMultiple(array $handlers): void
    {
        $this->registry->registerMultiple($handlers);
    }
}
