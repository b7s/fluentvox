<?php

/**
 * Example 8: Batch Processing Multiple Texts
 *
 * This example demonstrates how to process multiple texts efficiently,
 * useful for generating audio for multiple paragraphs, chapters, or items.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

// Sample texts to process
$texts = [
    'Chapter 1: The Beginning. It was a dark and stormy night.',
    'Chapter 2: The Journey. Our hero set out on a long adventure.',
    'Chapter 3: The Challenge. Obstacles appeared at every turn.',
    'Chapter 4: The Victory. Through perseverance, success was achieved.',
    'Chapter 5: The End. And they lived happily ever after.',
];

echo "Processing batch of " . count($texts) . " texts...\n\n";

$results = [];
$startTime = microtime(true);

foreach ($texts as $index => $text) {
    $chapterNum = $index + 1;
    echo "→ Processing Chapter {$chapterNum}: ";

    $result = FluentVox::make()
        ->text($text)
        ->forNarration()
        ->saveTo(__DIR__ . "/output/chapter-{$chapterNum}.wav")
        ->generate();

    if ($result->isSuccessful()) {
        echo "✓ {$result->getFormattedDuration()}\n";
        $results[] = $result;
    } else {
        echo "✗ Failed: {$result->error}\n";
    }
}

$endTime = microtime(true);
$totalTime = $endTime - $startTime;

// Calculate statistics
$totalDuration = array_reduce($results, fn($carry, $r) => $carry + $r->getDuration(), 0);
$avgDuration = count($results) > 0 ? $totalDuration / count($results) : 0;

echo "\n" . str_repeat('=', 50) . "\n";
echo "Batch Processing Complete!\n";
echo str_repeat('=', 50) . "\n";
echo "  Files processed: " . count($results) . "/" . count($texts) . "\n";
echo "  Total audio duration: " . gmdate('H:i:s', (int)$totalDuration) . "\n";
echo "  Average duration: " . number_format($avgDuration, 2) . "s\n";
echo "  Processing time: " . number_format($totalTime, 2) . "s\n";
echo "  Speed: " . number_format($totalDuration / $totalTime, 2) . "x realtime\n";
