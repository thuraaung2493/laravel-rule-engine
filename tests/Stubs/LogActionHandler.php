<?php

namespace Thuraaung\RuleEngine\Tests\Stubs;

use Thuraaung\RuleEngine\Actions\ActionResult;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;

class LogActionHandler implements ActionHandlerInterface
{
    public function handle(array $actionValue, array $context): ActionResult
    {
        if (!isset($actionValue['message'])) {
            return ActionResult::failure('Message is required for logging');
        }

        return ActionResult::success([
            'logged_message' => $actionValue['message'],
            'timestamp' => time(),
        ]);
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'log';
    }
}
