<?php

declare(strict_types=1);

use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Exceptions\ChatterboxNotInstalledException;
use B7s\FluentVox\Exceptions\FluentVoxException;
use B7s\FluentVox\Exceptions\GenerationException;
use B7s\FluentVox\Exceptions\ModelNotFoundException;
use B7s\FluentVox\Exceptions\PythonNotFoundException;

test('FluentVoxException is base exception', function () {
    $exception = new FluentVoxException('Test error');

    expect($exception)->toBeInstanceOf(\Exception::class)
        ->and($exception->getMessage())->toBe('Test error');
});

test('GenerationException creates failed exception', function () {
    $exception = GenerationException::failed('Generation error');

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('Generation error');
});

test('GenerationException creates timeout exception', function () {
    $exception = GenerationException::timeout(300);

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('300 seconds');
});

test('GenerationException creates invalid text exception', function () {
    $exception = GenerationException::invalidText();

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('required');
});

test('GenerationException creates text too long exception', function () {
    $exception = GenerationException::textTooLong(500, 300);

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('500')
        ->and($exception->getMessage())->toContain('300');
});

test('GenerationException creates invalid audio prompt exception', function () {
    $exception = GenerationException::invalidAudioPrompt('/path/to/file.wav');

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('/path/to/file.wav');
});

test('PythonNotFoundException creates not installed exception', function () {
    $exception = PythonNotFoundException::notInstalled();

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('Python');
});

test('PythonNotFoundException creates version too low exception', function () {
    $exception = PythonNotFoundException::versionTooLow('3.8.0');

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('3.8.0');
});

test('ChatterboxNotInstalledException creates not installed exception', function () {
    $exception = ChatterboxNotInstalledException::notInstalled();

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('Chatterbox');
});

test('ModelNotFoundException creates not downloaded exception', function () {
    $exception = ModelNotFoundException::notDownloaded(Model::Chatterbox);

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('chatterbox');
});

test('ModelNotFoundException creates download failed exception', function () {
    $exception = ModelNotFoundException::downloadFailed(Model::Chatterbox, 'Network error');

    expect($exception)->toBeInstanceOf(FluentVoxException::class)
        ->and($exception->getMessage())->toContain('chatterbox')
        ->and($exception->getMessage())->toContain('Network error');
});
