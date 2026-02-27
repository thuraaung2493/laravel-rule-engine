<?php

use Thuraaung\RuleEngine\Actions\ActionResult;
use Thuraaung\RuleEngine\Tests\Stubs\LogActionHandler;

describe('Custom Action Handler', function (): void {
    it('properly handles successful log action', function (): void {
        $handler = new LogActionHandler;

        $actionResult = $handler->handle(
            ['message' => 'Test message'],
            ['context' => 'data']
        );

        expect($actionResult)->toBeInstanceOf(ActionResult::class);
        expect($actionResult->success)->toBeTrue();
        expect($actionResult->context)->toHaveKey('logged_message', 'Test message');
        expect($actionResult->context)->toHaveKey('timestamp');
    });

    it('accumulates initial context', function (): void {
        $handler = new LogActionHandler;

        $actionResult = $handler->handle(
            ['message' => 'Test message'],
            ['initial' => 'data']
        );

        expect($actionResult)->toBeInstanceOf(ActionResult::class);
        expect($actionResult->success)->toBeTrue();
        expect($actionResult->context)->toHaveKey('logged_message', 'Test message');
        expect($actionResult->context)->toHaveKey('timestamp');
        expect($actionResult->context)->toHaveKey('initial', 'data');
    });

    it('handles missing message parameter', function (): void {
        $handler = new LogActionHandler;

        $actionResult = $handler->handle(
            [],
            ['context' => 'data']
        );

        expect($actionResult)->toBeInstanceOf(ActionResult::class);
        expect($actionResult->success)->toBeFalse();
        expect($actionResult->error)->toBe('Message is required for logging');
    });

    it('validates action type support', function (): void {
        $handler = new LogActionHandler;

        expect($handler->supports('log'))->toBeTrue();
        expect($handler->supports('other_action'))->toBeFalse();
    });
});
