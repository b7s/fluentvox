<?php

declare(strict_types=1);

use B7s\FluentVox\Results\GenerationResult;

test('creates successful result', function () {
    $result = GenerationResult::success(
        outputPath: '/path/to/output.wav',
        text: 'Hello, world!',
        sampleRate: 24000,
        duration: 2.5,
        metadata: ['model' => 'chatterbox'],
    );

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->success)->toBeTrue()
        ->and($result->outputPath)->toBe('/path/to/output.wav')
        ->and($result->text)->toBe('Hello, world!')
        ->and($result->sampleRate)->toBe(24000)
        ->and($result->duration)->toBe(2.5)
        ->and($result->error)->toBeNull();
});

test('creates failed result', function () {
    $result = GenerationResult::failure('Something went wrong');

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->success)->toBeFalse()
        ->and($result->error)->toBe('Something went wrong')
        ->and($result->outputPath)->toBeNull();
});

test('gets output path', function () {
    $result = GenerationResult::success(
        outputPath: '/path/to/output.wav',
        text: 'Test',
        sampleRate: 24000,
        duration: 1.0,
    );

    expect($result->getPath())->toBe('/path/to/output.wav');
});

test('gets duration', function () {
    $result = GenerationResult::success(
        outputPath: '/path/to/output.wav',
        text: 'Test',
        sampleRate: 24000,
        duration: 3.75,
    );

    expect($result->getDuration())->toBe(3.75);
});

test('formats duration correctly', function () {
    $result = GenerationResult::success(
        outputPath: '/path/to/output.wav',
        text: 'Test',
        sampleRate: 24000,
        duration: 125.5,
    );

    $formatted = $result->getFormattedDuration();
    
    expect($formatted)->toMatch('/^\d{2}:\d{2}\.\d{2}$/')
        ->and($formatted)->toContain('02:05');
});

test('converts to array', function () {
    $result = GenerationResult::success(
        outputPath: '/path/to/output.wav',
        text: 'Hello',
        sampleRate: 24000,
        duration: 2.0,
        metadata: ['model' => 'chatterbox'],
    );

    $array = $result->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys([
            'success',
            'output_path',
            'text',
            'sample_rate',
            'duration',
            'duration_formatted',
            'error',
            'metadata',
        ])
        ->and($array['success'])->toBeTrue()
        ->and($array['output_path'])->toBe('/path/to/output.wav');
});
