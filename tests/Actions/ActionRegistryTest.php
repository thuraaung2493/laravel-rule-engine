<?php

use Thuraaung\RuleEngine\Actions\ActionRegistry;
use Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface;
use Thuraaung\RuleEngine\Exceptions\ActionHandlerNotFoundException;

describe('ActionRegistryTest', function (): void {
    it('can check handler existence', function (): void {
        $registry = new ActionRegistry;

        expect($registry->has('test_action'))->toBeFalse();

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $mock);

        expect($registry->has('test_action'))->toBeTrue();
    });

    it('can get handler', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $mock);

        expect($registry->get('test_action'))->toBe($mock);
    });

    it('can register actions', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $mock);

        expect($registry->has('test_action'))->toBeTrue();
        expect($registry->get('test_action'))->toBe($mock);
    });

    it('can register multiple actions', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action1')->andReturn(true);

        $handler2 = Mockery::mock(ActionHandlerInterface::class);
        $handler2->shouldReceive('supports')->with('test_action2')->andReturn(true);

        $registry->register('test_action1', $mock);
        $registry->register('test_action2', $handler2);

        expect($registry->has('test_action1'))->toBeTrue();
        expect($registry->has('test_action2'))->toBeTrue();
        expect($registry->get('test_action1'))->toBe($mock);
        expect($registry->get('test_action2'))->toBe($handler2);
    });

    it('throws exception when registering unsupported action type', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action')->andReturn(false);

        expect(fn () => $registry->register('test_action', $mock))
            ->toThrow(InvalidArgumentException::class, 'Handler does not support action type: test_action');
    });

    it('prevents duplicate handler registration', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action')->andReturn(true);

        $handler2 = Mockery::mock(ActionHandlerInterface::class);
        $handler2->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $mock);

        // Should override the first handler
        $registry->register('test_action', $handler2);

        expect($registry->get('test_action'))->toBe($handler2);
    });

    it('supports multiple action types for single handler', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('action1')->andReturn(true);
        $mock->shouldReceive('supports')->with('action2')->andReturn(true);

        $registry->register('action1', $mock);
        $registry->register('action2', $mock);

        expect($registry->has('action1'))->toBeTrue();
        expect($registry->has('action2'))->toBeTrue();
        expect($registry->get('action1'))->toBe($mock);
        expect($registry->get('action2'))->toBe($mock);
    });

    it('throws exception when getting non-existent handler', function (): void {
        $registry = new ActionRegistry;

        expect(fn (): \Thuraaung\RuleEngine\Actions\Contracts\ActionHandlerInterface => $registry->get('nonexistent_action'))
            ->toThrow(ActionHandlerNotFoundException::class);
    });

    it('checks handler existence correctly', function (): void {
        $registry = new ActionRegistry;

        $mock = Mockery::mock(ActionHandlerInterface::class);
        $mock->shouldReceive('supports')->with('test_action')->andReturn(true);

        $registry->register('test_action', $mock);

        expect($registry->has('test_action'))->toBeTrue();
        expect($registry->has('nonexistent_action'))->toBeFalse();
    });
});
