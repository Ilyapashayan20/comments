<?php

use Relaticle\Comments\Config;

it('returns false for isBroadcastingEnabled by default', function () {
    expect(Config::isBroadcastingEnabled())->toBeFalse();
});

it('returns true for isBroadcastingEnabled when config overridden', function () {
    config()->set('comments.broadcasting.enabled', true);

    expect(Config::isBroadcastingEnabled())->toBeTrue();
});

it('returns comments as default broadcast channel prefix', function () {
    expect(Config::getBroadcastChannelPrefix())->toBe('comments');
});

it('returns custom broadcast channel prefix when overridden', function () {
    config()->set('comments.broadcasting.channel_prefix', 'my-app-comments');

    expect(Config::getBroadcastChannelPrefix())->toBe('my-app-comments');
});

it('returns 10s as default polling interval', function () {
    expect(Config::getPollingInterval())->toBe('10s');
});

it('returns custom polling interval when overridden', function () {
    config()->set('comments.polling.interval', '30s');

    expect(Config::getPollingInterval())->toBe('30s');
});
