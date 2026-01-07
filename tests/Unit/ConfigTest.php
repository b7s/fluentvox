<?php

declare(strict_types=1);

use B7s\FluentVox\Config;

beforeEach(function () {
    Config::reset();
});

afterEach(function () {
    Config::reset();
});

test('loads default configuration', function () {
    $config = Config::load();

    expect($config)->toBeArray()
        ->and($config['default_model'])->toBe('chatterbox')
        ->and($config['device'])->toBe('auto')
        ->and($config['timeout'])->toBe(300);
});

test('gets configuration value by key', function () {
    expect(Config::get('default_model'))->toBe('chatterbox')
        ->and(Config::get('device'))->toBe('auto')
        ->and(Config::get('timeout'))->toBe(300);
});

test('gets nested configuration value', function () {
    expect(Config::get('defaults.exaggeration'))->toBe(0.5)
        ->and(Config::get('defaults.temperature'))->toBe(0.6)
        ->and(Config::get('defaults.cfg_weight'))->toBe(0.5);
});

test('returns default value for missing key', function () {
    expect(Config::get('nonexistent', 'default'))->toBe('default')
        ->and(Config::get('nested.missing', 42))->toBe(42);
});

test('returns defaults when no config file exists', function () {
    $defaults = Config::defaults();

    expect($defaults)->toBeArray()
        ->and($defaults)->toHaveKey('default_model')
        ->and($defaults)->toHaveKey('device')
        ->and($defaults)->toHaveKey('timeout');
});
