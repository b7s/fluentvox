<?php

/**
 * Example 4: Expression and Emotion Control
 *
 * This example demonstrates how to control the expressiveness and
 * emotional intensity of the generated speech using different settings.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

$text = 'Wow, this is absolutely amazing! I cannot believe how incredible this technology is!';

echo "Generating speech with different expression levels...\n\n";

// 1. Subtle expression (calm, understated)
echo "→ Subtle expression: ";
$result = FluentVox::make()
    ->text($text)
    ->subtle()
    ->saveTo(__DIR__ . '/output/expression-subtle.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 2. Neutral expression (balanced)
echo "→ Neutral expression: ";
$result = FluentVox::make()
    ->text($text)
    ->neutral()
    ->saveTo(__DIR__ . '/output/expression-neutral.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 3. Expressive (animated, emotional)
echo "→ Expressive: ";
$result = FluentVox::make()
    ->text($text)
    ->expressive()
    ->saveTo(__DIR__ . '/output/expression-expressive.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 4. Dramatic (highly expressive, theatrical)
echo "→ Dramatic: ";
$result = FluentVox::make()
    ->text($text)
    ->dramatic()
    ->saveTo(__DIR__ . '/output/expression-dramatic.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 5. Custom exaggeration value
echo "→ Custom (0.9): ";
$result = FluentVox::make()
    ->text($text)
    ->exaggeration(0.9)
    ->saveTo(__DIR__ . '/output/expression-custom.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

echo "\n✓ All expression variations generated!\n";
echo "  Listen to the files to hear the differences in emotional intensity.\n";
