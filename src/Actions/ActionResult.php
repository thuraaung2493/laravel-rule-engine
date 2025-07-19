<?php

namespace Thuraaung\RuleEngine\Actions;

class ActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly array $context = [],
        public readonly ?string $error = null
    ) {}

    public static function success(array $context = []): self
    {
        return new self(true, $context);
    }

    public static function failure(string $error): self
    {
        return new self(false, [], $error);
    }
}
