<?php

declare(strict_types=1);

use B7s\FluentVox\Enums\OperatingSystem;
use B7s\FluentVox\Support\Platform;

beforeEach(function () {
    Platform::reset();
});

test('detects operating system', function () {
    $os = Platform::os();

    expect($os)->toBeInstanceOf(OperatingSystem::class)
        ->and($os->value)->toBeIn(['linux', 'darwin', 'windows']);
});

test('detects architecture', function () {
    $arch = Platform::architecture();

    expect($arch)->toBeString()
        ->and($arch)->toBeIn(['x86_64', 'arm64', 'x86']);
});

test('checks if running on linux', function () {
    $isLinux = Platform::isLinux();

    expect($isLinux)->toBeBool();
});

test('checks if running on macos', function () {
    $isMacOS = Platform::isMacOS();

    expect($isMacOS)->toBeBool();
});

test('checks if running on windows', function () {
    $isWindows = Platform::isWindows();

    expect($isWindows)->toBeBool();
});

test('gets home directory', function () {
    $home = Platform::homeDirectory();

    expect($home)->toBeString()
        ->and($home)->not->toBeEmpty();
});

test('gets cache directory', function () {
    $cache = Platform::cacheDirectory();

    expect($cache)->toBeString()
        ->and($cache)->toContain('fluentvox');
});

test('normalizes paths correctly', function () {
    $path = Platform::normalizePath('/path/to//file');

    expect($path)->not->toContain('//')
        ->and($path)->toBeString();
});

test('joins paths correctly', function () {
    $path = Platform::joinPath('path', 'to', 'file');

    expect($path)->toBeString()
        ->and($path)->toContain('path')
        ->and($path)->toContain('file');
});

test('returns platform info', function () {
    $info = Platform::info();

    expect($info)->toBeArray()
        ->and($info)->toHaveKeys([
            'os',
            'os_name',
            'architecture',
            'php_version',
            'home_directory',
            'cache_directory',
        ]);
});
