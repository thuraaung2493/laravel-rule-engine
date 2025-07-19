<?php

namespace Thuraaung\RuleEngine\Exceptions;

class ActionHandlerNotFoundException extends \RuntimeException
{
    public function __construct(
        string $actionType,
        int $code = 0,
        \Throwable|null $previous = null
    ) {
        $message = sprintf('No handler found for action type: %s', $actionType);
        parent::__construct($message, $code, $previous);
    }
}
