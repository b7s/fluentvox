<?php

/**
 * Example 5: Pace and Speed Control
 *
 * This example shows how to control the speaking speed and rhythm
 * of the generated speech using CFG weight adjustments.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

$text = 'The quick brown fox jumps over the lazy dog. This sentence contains every letter of the alphabet.';

echo "Generating speech with different pacing...\n\n";

// 1. Slow pace (deliberate, measured)
echo "→ Slow pace: ";
$result = FluentVox::make()
    ->text($text)
    ->slow()
    ->saveTo(__DIR__ . '/output/pace-slow.wav')
    ->generate();
echo $result->isSuccessful() ? "✓ {$result->getFormattedDuration()}\n" : "✗\n";

// 2. Normal pace
echo "→ Normal pace: ";
$result = FluentVox::make()
    ->text($text)
    ->normalPace()
    ->saveTo(__DIR__ . '/output/pace-normal.wav')
    ->generate();
echo $result->isSuccessful() ? "✓ {$result->getFormattedDuration()}\n" : "✗\n";

// 3. Fast pace (quick, energetic)
echo "→ Fast pace: ";
$result = FluentVox::make()
    ->text($text)
    ->fast()
    ->saveTo(__DIR__ . '/output/pace-fast.wav')
    ->generate();
echo $result->isSuccessful() ? "✓ {$result->getFormattedDuration()}\n" : "✗\n";

// 4. Custom CFG weight
echo "→ Custom (0.25 - very slow): ";
$result = FluentVox::make()
    ->text($text)
    ->cfgWeight(0.25)
    ->saveTo(__DIR__ . '/output/pace-very-slow.wav')
    ->generate();
echo $result->isSuccessful() ? "✓ {$result->getFormattedDuration()}\n" : "✗\n";

echo "\n✓ All pace variations generated!\n";
echo "  Notice the duration differences between slow and fast versions.\n";
