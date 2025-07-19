<?php

use Thuraaung\RuleEngine\Tests\Stubs\LogActionHandler;
use Thuraaung\RuleEngine\Actions\ActionResult;

it('properly handles successful log action', function () {
    $handler = new LogActionHandler();

    $result = $handler->handle(
        ['message' => 'Test message'],
        ['context' => 'data']
    );

    expect($result)->toBeInstanceOf(ActionResult::class);
    expect($result->success)->toBeTrue();
    expect($result->context)->toHaveKey('logged_message', 'Test message');
    expect($result->context)->toHaveKey('timestamp');
});

it('handles missing message parameter', function () {
    $handler = new LogActionHandler();

    $result = $handler->handle(
        [],
        ['context' => 'data']
    );

    expect($result)->toBeInstanceOf(ActionResult::class);
    expect($result->success)->toBeFalse();
    expect($result->error)->toBe('Message is required for logging');
});

it('validates action type support', function () {
    $handler = new LogActionHandler();

    expect($handler->supports('log'))->toBeTrue();
    expect($handler->supports('other_action'))->toBeFalse();
});
