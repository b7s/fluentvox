<?php

/**
 * Example 11: Error Handling and Validation
 *
 * This example demonstrates proper error handling and validation
 * when working with FluentVox.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;
use B7s\FluentVox\Exceptions\GenerationException;
use B7s\FluentVox\Exceptions\FluentVoxException;

echo "Error Handling Examples\n";
echo str_repeat('=', 50) . "\n\n";

// Example 1: Check system requirements before generation
echo "1. Checking system requirements:\n";
$requirements = FluentVox::checkRequirements();

if ($requirements['passed']) {
    echo "   ✓ All requirements met\n";
} else {
    echo "   ⚠ Some requirements not met:\n";
    foreach ($requirements['checks'] as $name => $check) {
        if (!$check['status'] && !($check['optional'] ?? false)) {
            echo "     - {$name}: {$check['message']}\n";
        }
    }
}
echo "\n";

// Example 2: Handle missing reference audio file
echo "2. Handling missing reference audio:\n";
try {
    $result = FluentVox::make()
        ->text('Test')
        ->voiceFrom('/nonexistent/file.wav')
        ->generate();
} catch (GenerationException $e) {
    echo "   ✓ Caught exception: {$e->getMessage()}\n";
}
echo "\n";

// Example 3: Handle empty text
echo "3. Handling empty text:\n";
try {
    $result = FluentVox::make()
        ->text('')
        ->generate();
} catch (GenerationException $e) {
    echo "   ✓ Caught exception: {$e->getMessage()}\n";
}
echo "\n";

// Example 4: Graceful error handling with result object
echo "4. Checking result status:\n";
$result = FluentVox::make()
    ->text('This might fail if dependencies are not installed.')
    ->saveTo(__DIR__ . '/output/error-test.wav')
    ->generate();

if ($result->isSuccessful()) {
    echo "   ✓ Generation successful\n";
    echo "     Output: {$result->outputPath}\n";
} else {
    echo "   ✗ Generation failed\n";
    echo "     Error: {$result->error}\n";
}
echo "\n";

// Example 5: Try-catch with detailed error information
echo "5. Detailed error handling:\n";
try {
    $result = FluentVox::make()
        ->text('Testing error handling.')
        ->timeout(1) // Very short timeout to potentially trigger error
        ->generate();

    if ($result->isSuccessful()) {
        echo "   ✓ Success: {$result->outputPath}\n";
    } else {
        echo "   ✗ Failed: {$result->error}\n";
    }
} catch (FluentVoxException $e) {
    echo "   ✗ Exception caught:\n";
    echo "     Type: " . get_class($e) . "\n";
    echo "     Message: {$e->getMessage()}\n";
    echo "     File: {$e->getFile()}:{$e->getLine()}\n";
}
echo "\n";

// Example 6: Validate before generation
echo "6. Pre-generation validation:\n";
$text = 'Valid text for generation.';
$outputPath = __DIR__ . '/output/validated.wav';

// Check if output directory exists
$outputDir = dirname($outputPath);
if (!is_dir($outputDir)) {
    echo "   → Creating output directory...\n";
    mkdir($outputDir, 0755, true);
}

// Check if output file already exists
if (file_exists($outputPath)) {
    echo "   ⚠ Output file already exists, will be overwritten\n";
}

// Proceed with generation
$result = FluentVox::make()
    ->text($text)
    ->saveTo($outputPath)
    ->generate();

if ($result->isSuccessful()) {
    echo "   ✓ Generated successfully\n";
    echo "     File size: " . number_format(filesize($outputPath) / 1024, 2) . " KB\n";
}

echo "\n✓ Error handling examples complete!\n";
