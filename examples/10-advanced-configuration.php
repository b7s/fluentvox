<?php

/**
 * Example 10: Advanced Configuration
 *
 * This example demonstrates advanced configuration options including
 * custom models, device selection, and fine-tuned parameters.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Enums\Device;

$text = 'This is an example of advanced configuration with fine-tuned parameters.';

echo "Advanced Configuration Examples\n";
echo str_repeat('=', 50) . "\n\n";

// Example 1: Turbo model with custom parameters
echo "1. Turbo model with paralinguistic tags:\n";
$result1 = FluentVox::make()
    ->turbo()
    ->text('Hello! [laugh] This is amazing! [cough] Excuse me.')
    ->exaggeration(0.6)
    ->temperature(0.7)
    ->cfgWeight(0.4)
    ->saveTo(__DIR__ . '/output/advanced-turbo.wav')
    ->generate();
echo $result1->isSuccessful() ? "   ✓ Generated\n\n" : "   ✗ Failed\n\n";

// Example 2: Force CPU mode (useful for testing)
echo "2. Force CPU mode:\n";
$result2 = FluentVox::make()
    ->text($text)
    ->cpu()
    ->saveTo(__DIR__ . '/output/advanced-cpu.wav')
    ->generate();
echo $result2->isSuccessful() ? "   ✓ Generated on CPU\n\n" : "   ✗ Failed\n\n";

// Example 3: Custom timeout for long texts
echo "3. Extended timeout for long text:\n";
$longText = str_repeat($text . ' ', 20);
$result3 = FluentVox::make()
    ->text($longText)
    ->timeout(600) // 10 minutes
    ->saveTo(__DIR__ . '/output/advanced-long.wav')
    ->generate();
echo $result3->isSuccessful() ? "   ✓ Generated ({$result3->getFormattedDuration()})\n\n" : "   ✗ Failed\n\n";

// Example 4: Fine-tuned parameters for specific voice style
echo "4. Fine-tuned dramatic reading:\n";
$result4 = FluentVox::make()
    ->text('The storm raged on, thunder echoing through the mountains!')
    ->exaggeration(0.85)
    ->temperature(1.0)
    ->cfgWeight(0.35)
    ->seed(42)
    ->saveTo(__DIR__ . '/output/advanced-dramatic.wav')
    ->generate();
echo $result4->isSuccessful() ? "   ✓ Generated\n\n" : "   ✗ Failed\n\n";

// Example 5: Verbose mode for debugging
echo "5. Verbose mode (debugging):\n";
$result5 = FluentVox::make()
    ->text($text)
    ->verbose()
    ->saveTo(__DIR__ . '/output/advanced-verbose.wav')
    ->generate();
echo $result5->isSuccessful() ? "   ✓ Generated\n\n" : "   ✗ Failed\n\n";

// Display metadata from last result
if ($result5->isSuccessful()) {
    echo "Metadata from last generation:\n";
    echo "  Model: {$result5->metadata['model']}\n";
    echo "  Device: {$result5->metadata['device']}\n";
    echo "  Exaggeration: {$result5->metadata['exaggeration']}\n";
    echo "  Temperature: {$result5->metadata['temperature']}\n";
    echo "  CFG Weight: {$result5->metadata['cfg_weight']}\n";
    echo "  Seed: {$result5->metadata['seed']}\n";
}

echo "\n✓ Advanced configuration examples complete!\n";
