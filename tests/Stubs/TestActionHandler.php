<?php

namespace Thuraaung\RuleEngine\Tests\Stubs;

// use Thuraaung\RuleEngine\Actions\ActionResult;
// use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;

// class TestActionHandler implements ActionHandlerInterface
// {
//     public function __construct(
//         private bool $shouldSucceed = true,
//         private array $contextToAdd = [],
//         private ?string $errorMessage = null
//     ) {}

//     public function supports(string $actionType): bool
//     {
//         return true;
//     }

//     public function handle(array $value, array $context): ActionResult
//     {
//         if (! $this->shouldSucceed) {
//             return new ActionResult(false, [], $this->errorMessage ?? 'Action failed');
//         }

//         return new ActionResult(true, array_merge($context, $this->contextToAdd));
//     }
// }
