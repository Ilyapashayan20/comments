<?php

use Relaticle\Comments\CommentsConfig;

it('returns false for isBroadcastingEnabled by default', function () {
    expect(CommentsConfig::isBroadcastingEnabled())->toBeFalse();
});

it('returns true for isBroadcastingEnabled when config overridden', function () {
    config()->set('comments.broadcasting.enabled', true);

    expect(CommentsConfig::isBroadcastingEnabled())->toBeTrue();
});

it('returns comments as default broadcast channel prefix', function () {
    expect(CommentsConfig::getBroadcastChannelPrefix())->toBe('comments');
});

it('returns custom broadcast channel prefix when overridden', function () {
    config()->set('comments.broadcasting.channel_prefix', 'my-app-comments');

    expect(CommentsConfig::getBroadcastChannelPrefix())->toBe('my-app-comments');
});

it('returns 10s as default polling interval', function () {
    expect(CommentsConfig::getPollingInterval())->toBe('10s');
});

it('returns custom polling interval when overridden', function () {
    config()->set('comments.polling.interval', '30s');

    expect(CommentsConfig::getPollingInterval())->toBe('30s');
});
