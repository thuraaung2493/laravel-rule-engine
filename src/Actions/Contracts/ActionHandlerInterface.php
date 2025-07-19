<?php

namespace Thuraaung\RuleEngine\Actions\Contracts;

use Thuraaung\RuleEngine\Actions\ActionResult;

interface ActionHandlerInterface
{
    public function handle(array $actionValue, array $context): ActionResult;

    public function supports(string $actionType): bool;
}
