<?php

use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Exceptions\ActionHandlerNotFoundException;

describe('ActionRegistryTest', function () {
    it('can check handler existence', function () {
        $registry = new ActionRegistry;

        expect($registry->has('test_action'))->toBeFalse();

        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $handler);

        expect($registry->has('test_action'))->toBeTrue();
    });

    it('can get handler', function () {
        $registry = new ActionRegistry;

        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $handler);

        expect($registry->get('test_action'))->toBe($handler);
    });

    it('can register actions', function () {
        $registry = new ActionRegistry;

        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $handler);

        expect($registry->has('test_action'))->toBeTrue();
        expect($registry->get('test_action'))->toBe($handler);
    });

    it('can register multiple actions', function () {
        $registry = new ActionRegistry;

        $handler1 = Mockery::mock(ActionHandlerInterface::class);
        $handler1->shouldReceive('supports')->with('test_action1')->andReturn(true);

        $handler2 = Mockery::mock(ActionHandlerInterface::class);
        $handler2->shouldReceive('supports')->with('test_action2')->andReturn(true);

        $registry->register('test_action1', $handler1);
        $registry->register('test_action2', $handler2);

        expect($registry->has('test_action1'))->toBeTrue();
        expect($registry->has('test_action2'))->toBeTrue();
        expect($registry->get('test_action1'))->toBe($handler1);
        expect($registry->get('test_action2'))->toBe($handler2);
    });

    it('throws exception when registering unsupported action type', function () {
        $registry = new ActionRegistry;

        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('test_action')->andReturn(false);

        expect(fn () => $registry->register('test_action', $handler))
            ->toThrow(InvalidArgumentException::class, 'Handler does not support action type: test_action');
    });

    it('prevents duplicate handler registration', function () {
        $registry = new ActionRegistry;

        $handler1 = Mockery::mock(ActionHandlerInterface::class);
        $handler1->shouldReceive('supports')->with('test_action')->andReturn(true);

        $handler2 = Mockery::mock(ActionHandlerInterface::class);
        $handler2->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $handler1);

        // Should override the first handler
        $registry->register('test_action', $handler2);

        expect($registry->get('test_action'))->toBe($handler2);
    });

    it('supports multiple action types for single handler', function () {
        $registry = new ActionRegistry;

        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('action1')->andReturn(true);
        $handler->shouldReceive('supports')->with('action2')->andReturn(true);

        $registry->register('action1', $handler);
        $registry->register('action2', $handler);

        expect($registry->has('action1'))->toBeTrue();
        expect($registry->has('action2'))->toBeTrue();
        expect($registry->get('action1'))->toBe($handler);
        expect($registry->get('action2'))->toBe($handler);
    });

    it('throws exception when getting non-existent handler', function () {
        $registry = new ActionRegistry;

        expect(fn () => $registry->get('nonexistent_action'))
            ->toThrow(ActionHandlerNotFoundException::class);
    });

    it('checks handler existence correctly', function () {
        $registry = new ActionRegistry;

        $handler = Mockery::mock(ActionHandlerInterface::class);
        $handler->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $handler);

        expect($registry->has('test_action'))->toBeTrue();
        expect($registry->has('nonexistent_action'))->toBeFalse();
    });
});
