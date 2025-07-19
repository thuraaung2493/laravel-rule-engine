<?php

use Thuraaung\RuleEngine\Actions\RuleActionHandler;
use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\ActionResult;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->registry = new ActionRegistry();
    $this->handler = new RuleActionHandler($this->registry);
});

it('handles null action values gracefully', function () {
    $handler = Mockery::mock(ActionHandlerInterface::class);
    $handler->shouldReceive('supports')->with('test_action')->andReturn(true);
    $handler->shouldReceive('handle')->andReturn(ActionResult::success());

    $this->registry->register('test_action', $handler);

    $context = [];
    $this->handler->handle('test_action', [], $context);

    expect($context)->toBe([]);
});

it('handles recursive action context merging', function () {
    $handler = Mockery::mock(ActionHandlerInterface::class);
    $handler->shouldReceive('supports')->with('test_action')->andReturn(true);
    $handler->shouldReceive('handle')
        ->andReturn(ActionResult::success(['nested' => ['key' => 'value']]));

    $this->registry->register('test_action', $handler);

    $context = ['existing' => 'data'];
    $this->handler->handle('test_action', [], $context);

    expect($context)->toBe([
        'existing' => 'data',
        'nested' => ['key' => 'value']
    ]);
});

it('logs warnings for unknown actions', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Unknown action type: unknown_action', ['value' => ['test' => 'data']]);

    $context = [];
    $this->handler->handle('unknown_action', ['test' => 'data'], $context);
});

it('handles action errors with proper logging', function () {
    Log::shouldReceive('error')
        ->once()
        ->with("Action 'test_action' failed: Test error");

    $handler = Mockery::mock(ActionHandlerInterface::class);
    $handler->shouldReceive('supports')->with('test_action')->andReturn(true);
    $handler->shouldReceive('handle')->andReturn(ActionResult::failure('Test error'));

    $this->registry->register('test_action', $handler);

    $context = [];
    $this->handler->handle('test_action', [], $context);
});

it('throws exception for unknown action when configured', function () {
    $handler = new RuleActionHandler($this->registry, true);

    $context = [];
    expect(function () use ($handler, $context) {
        $handler->handle('unknown_action', [], $context);
    })->toThrow(\InvalidArgumentException::class, 'Unknown action type: unknown_action');
});

it('throws exception for action failure when configured', function () {
    $handler = new RuleActionHandler($this->registry, true);

    $actionHandler = Mockery::mock(ActionHandlerInterface::class);
    $actionHandler->shouldReceive('supports')->with('test_action')->andReturn(true);
    $actionHandler->shouldReceive('handle')->andReturn(ActionResult::failure('Test error'));

    $this->registry->register('test_action', $actionHandler);

    $context = [];
    expect(fn() => $handler->handle('test_action', [], $context))
        ->toThrow(\RuntimeException::class, "Action 'test_action' failed: Test error");
});

it('preserves original context on action failure', function () {
    $handler = Mockery::mock(ActionHandlerInterface::class);
    $handler->shouldReceive('supports')->with('test_action')->andReturn(true);
    $handler->shouldReceive('handle')->andReturn(ActionResult::failure('Test error'));

    $this->registry->register('test_action', $handler);

    $context = ['original' => 'value'];
    $this->handler->handle('test_action', [], $context);

    expect($context)->toBe(['original' => 'value']);
});
