<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\ActionResult;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Actions\RuleActionHandler;

describe('Rule Action Handler', function () {
    beforeEach(function () {
        $this->registry = new ActionRegistry;
        $this->handler = new RuleActionHandler($this->registry);
    });

    it('handles registered actions', function () {
        $action = Mockery::mock(ActionHandlerInterface::class);
        $action->shouldReceive('supports')->with('test_action')->andReturnTrue();
        $action->shouldReceive('handle')->with(['foo' => 'bar'], [])->andReturn(ActionResult::success(['foo' => 'bar']));

        $this->registry->register('test_action', $action);
        $context = collect();

        $result = $this->handler->handle('test_action', ['foo' => 'bar'], $context);

        expect($result)->toBeInstanceOf(Collection::class);
        expect($result->get('foo'))->toBe('bar');
    });

    it('accumulates context values', function () {
        $action = Mockery::mock(ActionHandlerInterface::class);
        $action->shouldReceive('supports')->with('test_action')->andReturnTrue();
        $action->shouldReceive('handle')->with([], collect(['initial' => 'value']))
            ->andReturn(ActionResult::success(['key' => 'value']));

        $this->registry->register('test_action', $action);
        $context = collect(['initial' => 'value']);

        $result = $this->handler->handle('test_action', [], $context);

        expect($result->toArray())->toBe([
            'initial' => 'value',
            'key' => 'value',
        ]);
    });

    it('handles action errors gracefully', function () {
        $action = Mockery::mock(ActionHandlerInterface::class);
        $action->shouldReceive('supports')->with('test_action')->andReturnTrue();
        $action->shouldReceive('handle')->with([], collect())->andThrow(new \RuntimeException('Action failed'));

        $this->registry->register('test_action', $action);
        $context = collect();

        $result = $this->handler->handle('test_action', [], $context);

        expect($result)->toBeInstanceOf(Collection::class);
        expect($result->isEmpty())->toBeTrue();
    });

    it('handles action with no context gracefully', function () {
        $action = Mockery::mock(ActionHandlerInterface::class);
        $action->shouldReceive('supports')->with('test_action')->andReturnTrue();
        $action->shouldReceive('handle')->with([], collect())->andReturn(ActionResult::success());

        $this->registry->register('test_action', $action);
        $context = collect();

        $result = $this->handler->handle('test_action', [], $context);

        expect($result)->toBeInstanceOf(Collection::class);
        expect($result->isEmpty())->toBeTrue();
    });

    it('logs warnings for unknown actions', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unknown action type: unknown_action', ['value' => ['test' => 'data']]);

        $context = collect([]);
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

        $context = collect([]);
        $this->handler->handle('test_action', [], $context);
    });

    it('throws exception for unknown action when configured', function () {
        $handler = new RuleActionHandler($this->registry, true);

        $context = collect([]);
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

        $context = collect([]);
        expect(fn () => $handler->handle('test_action', [], $context))
            ->toThrow(\RuntimeException::class, "Action 'test_action' failed: Test error");
    });

    it('preserves original context on action failure', function () {
        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('test_action')->andReturn(true);
        $handler->shouldReceive('handle')->andReturn(ActionResult::failure('Test error'));

        $this->registry->register('test_action', $handler);

        $context = collect(['original' => 'value']);
        $this->handler->handle('test_action', [], $context);

        expect($context->toArray())->toBe(['original' => 'value']);
    });
});
