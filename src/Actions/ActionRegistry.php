<?php

namespace Thuraaung\RuleEngine\Actions;

use Illuminate\Support\Collection;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Exceptions\ActionHandlerNotFoundException;

class ActionRegistry
{
    protected array $handlers = [];

    public function register(string $actionType, ActionHandlerInterface $handler): void
    {
        if (! $handler->supports($actionType)) {
            throw new \InvalidArgumentException(
                "Handler does not support action type: {$actionType}"
            );
        }

        $this->handlers[$actionType] = $handler;
    }

    public function registerMultiple(array $handlers): void
    {
        collect($handlers)->each(function (ActionHandlerInterface $handler, string $actionType) {
            $this->register($actionType, $handler);
        });
    }

    public function get(string $actionType): ActionHandlerInterface
    {
        if (! isset($this->handlers[$actionType])) {
            throw new ActionHandlerNotFoundException($actionType);
        }

        return $this->handlers[$actionType];
    }

    public function has(string $actionType): bool
    {
        return isset($this->handlers[$actionType]);
    }

    public function getHandlers(): Collection
    {
        return collect($this->handlers);
    }

    public function unregister(string $actionType): bool
    {
        if (isset($this->handlers[$actionType])) {
            unset($this->handlers[$actionType]);

            return true;
        }

        return false;
    }

    public function clear(): void
    {
        $this->handlers = [];
    }

    public function count(): int
    {
        return count($this->handlers);
    }
}
