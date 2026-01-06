<?php

/**
 * Example 1: Basic Text-to-Speech
 *
 * This example demonstrates the simplest way to generate speech from text.
 * The audio will be saved to the current directory with an auto-generated filename.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

// Generate speech from text
$result = FluentVox::make()
    ->text('Hello, world! This is FluentVox speaking. Welcome to text-to-speech synthesis.')
    ->generate();

if ($result->isSuccessful()) {
    echo "✓ Audio generated successfully!\n";
    echo "  Output: {$result->outputPath}\n";
    echo "  Duration: {$result->getFormattedDuration()}\n";
    echo "  Sample rate: {$result->sampleRate} Hz\n";
} else {
    echo "✗ Generation failed: {$result->error}\n";
}
