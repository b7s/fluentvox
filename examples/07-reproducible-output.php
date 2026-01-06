<?php

/**
 * Example 7: Reproducible Output with Seeds
 *
 * This example shows how to use random seeds to generate
 * identical audio output across multiple runs.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

$text = 'This is a test of reproducible speech generation.';

echo "Generating speech with different seeds...\n\n";

// 1. Random generation (seed = 0)
echo "→ Random (no seed): ";
$result1 = FluentVox::make()
    ->text($text)
    ->seed(0)
    ->saveTo(__DIR__ . '/output/seed-random-1.wav')
    ->generate();
echo $result1->isSuccessful() ? "✓\n" : "✗\n";

// 2. Another random generation
echo "→ Random (no seed): ";
$result2 = FluentVox::make()
    ->text($text)
    ->seed(0)
    ->saveTo(__DIR__ . '/output/seed-random-2.wav')
    ->generate();
echo $result2->isSuccessful() ? "✓\n" : "✗\n";

// 3. Fixed seed (reproducible)
echo "→ Fixed seed (42): ";
$result3 = FluentVox::make()
    ->text($text)
    ->seed(42)
    ->saveTo(__DIR__ . '/output/seed-fixed-42-1.wav')
    ->generate();
echo $result3->isSuccessful() ? "✓\n" : "✗\n";

// 4. Same fixed seed (should be identical to #3)
echo "→ Fixed seed (42): ";
$result4 = FluentVox::make()
    ->text($text)
    ->seed(42)
    ->saveTo(__DIR__ . '/output/seed-fixed-42-2.wav')
    ->generate();
echo $result4->isSuccessful() ? "✓\n" : "✗\n";

// 5. Different fixed seed
echo "→ Fixed seed (123): ";
$result5 = FluentVox::make()
    ->text($text)
    ->seed(123)
    ->saveTo(__DIR__ . '/output/seed-fixed-123.wav')
    ->generate();
echo $result5->isSuccessful() ? "✓\n" : "✗\n";

echo "\n✓ All variations generated!\n";
echo "  Files with seed 42 should be identical.\n";
echo "  Random files will be different from each other.\n";
