<?php

/**
 * Example 9: Progress Monitoring
 *
 * This example shows how to monitor the generation progress in real-time
 * using callbacks, useful for long texts or when providing user feedback.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

$text = <<<TEXT
This is a longer text that will take some time to generate.
We can monitor the progress as the model processes the text and generates the audio.
This is useful for providing feedback to users in applications with a user interface.
The callback function receives output from the Python process in real-time.
TEXT;

echo "Generating speech with progress monitoring...\n";
echo str_repeat('=', 50) . "\n\n";

$startTime = microtime(true);

$result = FluentVox::make()
    ->text($text)
    ->saveTo(__DIR__ . '/output/progress-monitored.wav')
    ->onProgress(function (string $output, bool $isError) {
        // Filter and display relevant progress information
        $output = trim($output);
        
        if (empty($output)) {
            return;
        }

        // Show progress indicators
        if (str_contains($output, 'Using device:')) {
            echo "→ " . $output . "\n";
        } elseif (str_contains($output, 'Loading model')) {
            echo "→ " . $output . "\n";
        } elseif (str_contains($output, 'Generating audio')) {
            echo "→ " . $output . "\n";
        } elseif (str_contains($output, 'Audio saved')) {
            echo "→ " . $output . "\n";
        } elseif ($isError && !str_contains($output, 'FutureWarning')) {
            // Show errors (but filter out warnings)
            echo "⚠ " . $output . "\n";
        }
    })
    ->generate();

$endTime = microtime(true);
$processingTime = $endTime - $startTime;

echo "\n" . str_repeat('=', 50) . "\n";

if ($result->isSuccessful()) {
    echo "✓ Generation successful!\n";
    echo "  Output: {$result->outputPath}\n";
    echo "  Duration: {$result->getFormattedDuration()}\n";
    echo "  Processing time: " . number_format($processingTime, 2) . "s\n";
} else {
    echo "✗ Generation failed: {$result->error}\n";
}
