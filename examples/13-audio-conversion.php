<?php

/**
 * Example 13: Audio Format Conversion
 *
 * This example demonstrates how to convert generated audio
 * to different formats (MP3, M4A, OGG, Opus, FLAC).
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

$text = 'This audio will be converted to multiple formats for compatibility across different platforms.';

echo "Audio Format Conversion Examples\n";
echo str_repeat('=', 50) . "\n\n";

// Example 1: Generate and convert to MP3 (most compatible)
echo "1. Converting to MP3 (192 kbps):\n";
$result = FluentVox::make()
    ->text($text)
    ->convertToMp3(__DIR__ . '/output/converted.mp3', bitrate: 192);

if ($result->isSuccessful()) {
    $size = filesize($result->outputPath);
    echo "   ✓ MP3 created: " . number_format($size / 1024, 2) . " KB\n\n";
} else {
    echo "   ✗ Failed\n\n";
}

// Example 2: Convert to M4A/AAC (Apple devices)
echo "2. Converting to M4A/AAC (128 kbps):\n";
$result = FluentVox::make()
    ->text($text)
    ->convertToM4a(__DIR__ . '/output/converted.m4a', bitrate: 128);

if ($result->isSuccessful()) {
    $size = filesize($result->outputPath);
    echo "   ✓ M4A created: " . number_format($size / 1024, 2) . " KB\n\n";
} else {
    echo "   ✗ Failed\n\n";
}

// Example 3: Convert to OGG Vorbis (web streaming)
echo "3. Converting to OGG Vorbis (quality 5):\n";
$result = FluentVox::make()
    ->text($text)
    ->convertToOgg(__DIR__ . '/output/converted.ogg', quality: 5);

if ($result->isSuccessful()) {
    $size = filesize($result->outputPath);
    echo "   ✓ OGG created: " . number_format($size / 1024, 2) . " KB\n\n";
} else {
    echo "   ✗ Failed\n\n";
}

// Example 4: Convert to Opus (best compression)
echo "4. Converting to Opus (96 kbps):\n";
$result = FluentVox::make()
    ->text($text)
    ->convertToOpus(__DIR__ . '/output/converted.opus', bitrate: 96);

if ($result->isSuccessful()) {
    $size = filesize($result->outputPath);
    echo "   ✓ Opus created: " . number_format($size / 1024, 2) . " KB\n\n";
} else {
    echo "   ✗ Failed\n\n";
}

// Example 5: Convert to FLAC (lossless)
echo "5. Converting to FLAC (lossless):\n";
$result = FluentVox::make()
    ->text($text)
    ->convertToFlac(__DIR__ . '/output/converted.flac');

if ($result->isSuccessful()) {
    $size = filesize($result->outputPath);
    echo "   ✓ FLAC created: " . number_format($size / 1024, 2) . " KB\n\n";
} else {
    echo "   ✗ Failed\n\n";
}

// Example 6: Generate WAV first, then convert manually
echo "6. Manual conversion (WAV → MP3):\n";
$wavResult = FluentVox::make()
    ->text($text)
    ->saveTo(__DIR__ . '/output/original.wav')
    ->generate();

if ($wavResult->isSuccessful()) {
    echo "   → WAV generated\n";
    
    // Convert using static helper
    $success = FluentVox::convertAudio(
        $wavResult->outputPath,
        __DIR__ . '/output/manual-converted.mp3',
        'mp3',
        ['bitrate' => 320]
    );
    
    if ($success) {
        echo "   ✓ Converted to MP3 (320 kbps)\n\n";
    } else {
        echo "   ✗ Conversion failed\n\n";
    }
}

// Example 7: Get audio file information
echo "7. Audio file information:\n";
$info = FluentVox::getAudioInfo(__DIR__ . '/output/converted.mp3');

if ($info) {
    echo "   Duration: " . gmdate('H:i:s', (int)$info['duration']) . "\n";
    echo "   Sample rate: {$info['sample_rate']} Hz\n";
    echo "   Channels: {$info['channels']}\n";
    echo "   Codec: {$info['codec']}\n";
    echo "   Bitrate: " . number_format($info['bitrate'] / 1000, 0) . " kbps\n\n";
}

// Example 8: Convert and delete original
echo "8. Convert and delete original WAV:\n";
$result = FluentVox::make()
    ->text('This WAV file will be deleted after conversion.')
    ->convertToMp3(
        __DIR__ . '/output/no-wav.mp3',
        bitrate: 192,
        deleteOriginal: true
    );

if ($result->isSuccessful()) {
    echo "   ✓ MP3 created, original WAV deleted\n\n";
}

echo str_repeat('=', 50) . "\n";
echo "✓ All conversions complete!\n\n";

echo "Format recommendations:\n";
echo "  • MP3: Universal compatibility, good compression\n";
echo "  • M4A: Apple devices, slightly better quality than MP3\n";
echo "  • OGG: Web streaming, open format\n";
echo "  • Opus: Best compression, modern browsers\n";
echo "  • FLAC: Lossless, archival quality\n\n";

// Example 9: Using the universal convertTo() method
echo str_repeat('=', 50) . "\n";
echo "Universal convertTo() Method\n";
echo str_repeat('=', 50) . "\n\n";

echo "The convertTo() method auto-detects format from extension:\n\n";

// Auto-detect MP3
echo "→ Auto-detect MP3: ";
$result = FluentVox::make()
    ->text('Format detected from .mp3 extension')
    ->convertTo(__DIR__ . '/output/auto-detect.mp3');
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// Auto-detect M4A with custom bitrate
echo "→ Auto-detect M4A (192 kbps): ";
$result = FluentVox::make()
    ->text('Format detected from .m4a extension')
    ->convertTo(__DIR__ . '/output/auto-detect.m4a', ['bitrate' => 192]);
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// Auto-detect OGG with custom quality
echo "→ Auto-detect OGG (quality 7): ";
$result = FluentVox::make()
    ->text('Format detected from .ogg extension')
    ->convertTo(__DIR__ . '/output/auto-detect.ogg', ['quality' => 7]);
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// Invalid extension handling
echo "→ Invalid extension (.xyz): ";
try {
    FluentVox::make()
        ->text('This will fail')
        ->convertTo(__DIR__ . '/output/invalid.xyz');
    echo "✗ Should have thrown exception\n";
} catch (\InvalidArgumentException $e) {
    echo "✓ Exception caught\n";
}

echo "\n✓ Universal method works with all supported formats!\n";
echo "  Supported: .mp3, .m4a, .aac, .ogg, .opus, .flac\n";

