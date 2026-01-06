<?php

/**
 * Example 2: Voice Cloning
 *
 * This example shows how to clone a voice from a reference audio file.
 * The generated speech will mimic the voice characteristics of the reference speaker.
 *
 * Requirements:
 * - A reference audio file (WAV, MP3, FLAC) with clear speech
 * - Ideally 3-10 seconds of clean audio
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

// Path to your reference audio file
$referenceAudio = __DIR__ . '/reference-voice.wav';

// Check if reference file exists
if (!file_exists($referenceAudio)) {
    echo "⚠ Reference audio file not found: {$referenceAudio}\n";
    echo "  Please provide a reference audio file to clone the voice.\n";
    exit(1);
}

// Generate speech with cloned voice
$result = FluentVox::make()
    ->text('Hello! I am speaking with a cloned voice. This technology allows me to sound like the reference speaker.')
    ->voiceFrom($referenceAudio)
    ->saveTo(__DIR__ . '/output/cloned-voice.wav')
    ->generate();

if ($result->isSuccessful()) {
    echo "✓ Voice cloning successful!\n";
    echo "  Output: {$result->outputPath}\n";
    echo "  Duration: {$result->getFormattedDuration()}\n";
    echo "\n";
    echo "  The generated speech should sound similar to the reference voice.\n";
} else {
    echo "✗ Generation failed: {$result->error}\n";
}
