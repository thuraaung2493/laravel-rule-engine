<?php

use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\RuleActionHandler;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Actions\ActionResult;

class TestActionHandler implements ActionHandlerInterface
{
    private bool $shouldSucceed;
    private array $contextToAdd;
    private ?string $errorMessage;

    public function __construct(bool $shouldSucceed = true, array $contextToAdd = [], ?string $errorMessage = null)
    {
        $this->shouldSucceed = $shouldSucceed;
        $this->contextToAdd = $contextToAdd;
        $this->errorMessage = $errorMessage;
    }

    public function supports(string $actionType): bool
    {
        return true;
    }

    public function handle(array $value, array $context): ActionResult
    {
        if (!$this->shouldSucceed) {
            return new ActionResult(false, [], $this->errorMessage ?? 'Action failed');
        }

        return new ActionResult(true, array_merge($context, $this->contextToAdd));
    }
}

test('it accumulates context across multiple actions', function () {
    $registry = new ActionRegistry();
    $handler = new RuleActionHandler($registry);

    $action1 = new TestActionHandler(true, ['key1' => 'value1']);
    $action2 = new TestActionHandler(true, ['key2' => 'value2']);

    $registry->register('action1', $action1);
    $registry->register('action2', $action2);

    $context = ['initial' => 'value'];

    $handler->handle('action1', [], $context);
    $handler->handle('action2', [], $context);

    expect($context)->toBe([
        'initial' => 'value',
        'key1' => 'value1',
        'key2' => 'value2'
    ]);
});

test('it preserves existing context values on action failure', function () {
    $registry = new ActionRegistry();
    $handler = new RuleActionHandler($registry);

    $action1 = new TestActionHandler(true, ['key1' => 'value1']);
    $action2 = new TestActionHandler(false, [], 'Failed action');

    $registry->register('action1', $action1);
    $registry->register('action2', $action2);

    $context = ['initial' => 'value'];

    $handler->handle('action1', [], $context);
    $handler->handle('action2', [], $context);

    expect($context)->toBe([
        'initial' => 'value',
        'key1' => 'value1'
    ]);
});

test('it handles empty action values', function () {
    $registry = new ActionRegistry();
    $handler = new RuleActionHandler($registry);

    $action = new TestActionHandler(true, ['key' => 'value']);
    $registry->register('test_action', $action);

    $context = [];
    $handler->handle('test_action', [], $context);

    expect($context)->toBe(['key' => 'value']);
});

test('it handles complex nested context values', function () {
    $registry = new ActionRegistry();
    $handler = new RuleActionHandler($registry);

    $action = new TestActionHandler(true, [
        'nested' => [
            'deep' => [
                'value' => 42
            ]
        ]
    ]);
    $registry->register('test_action', $action);

    $context = [
        'existing' => [
            'data' => 'value'
        ]
    ];

    $handler->handle('test_action', [], $context);

    expect($context)->toBe([
        'existing' => [
            'data' => 'value'
        ],
        'nested' => [
            'deep' => [
                'value' => 42
            ]
        ]
    ]);
});

test('it handles concurrent action failures gracefully', function () {
    $registry = new ActionRegistry();
    $handler = new RuleActionHandler($registry, true);

    $action1 = new TestActionHandler(false, [], 'Error 1');
    $action2 = new TestActionHandler(false, [], 'Error 2');

    $registry->register('action1', $action1);
    $registry->register('action2', $action2);

    $context = ['initial' => 'value'];

    expect(fn() => $handler->handle('action1', [], $context))->toThrow(\RuntimeException::class);
    expect(fn() => $handler->handle('action2', [], $context))->toThrow(\RuntimeException::class);
    expect($context)->toBe(['initial' => 'value']);
});
