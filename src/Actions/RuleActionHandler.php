<?php

namespace Thuraaung\RuleEngine\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RuleActionHandler
{
    public function __construct(
        protected ActionRegistry $registry,
        protected bool $throwOnError = false
    ) {}

    public function handle(string $actionType, array $actionValue, Collection $context): Collection
    {
        if (! $this->registry->has($actionType)) {
            $this->handleUnknownAction($actionType, $actionValue);

            return $context;
        }

        try {
            $handler = $this->registry->get($actionType);
            $result = $handler->handle($actionValue, $context->toArray());

            if ($result->success) {
                return $context->merge(collect($result->context));
            }

            $this->handleActionError($actionType, $result->error);

            return $context;
        } catch (\Throwable $e) {
            $this->handleActionError($actionType, $e->getMessage());

            return $context;
        }
    }

    protected function handleUnknownAction(string $type, array $value): void
    {
        $message = "Unknown action type: {$type}";

        if ($this->throwOnError) {
            throw new \InvalidArgumentException($message);
        }

        Log::warning($message, ['value' => $value]);
    }

    protected function handleActionError(string $type, ?string $error): void
    {
        $message = "Action '{$type}' failed".($error ? ": {$error}" : '');
        Log::error($message);

        if ($this->throwOnError) {
            throw new \RuntimeException($message);
        }
    }

    protected function getRegistry(): ActionRegistry
    {
        return $this->registry;
    }
}
