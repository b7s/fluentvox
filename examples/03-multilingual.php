<?php

/**
 * Example 3: Multilingual Speech Synthesis
 *
 * This example demonstrates how to generate speech in multiple languages
 * using the Chatterbox Multilingual model.
 *
 * Supported languages: Arabic, Danish, German, Greek, English, Spanish,
 * Finnish, French, Hebrew, Hindi, Italian, Japanese, Korean, Malay,
 * Dutch, Norwegian, Polish, Portuguese, Russian, Swedish, Swahili,
 * Turkish, Chinese
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;
use B7s\FluentVox\Enums\Language;

// Define texts in different languages
$texts = [
    ['lang' => Language::English, 'text' => 'Hello, how are you today?', 'file' => 'english.wav'],
    ['lang' => Language::French, 'text' => 'Bonjour, comment allez-vous aujourd\'hui?', 'file' => 'french.wav'],
    ['lang' => Language::Spanish, 'text' => '¡Hola! ¿Cómo estás hoy?', 'file' => 'spanish.wav'],
    ['lang' => Language::German, 'text' => 'Hallo, wie geht es dir heute?', 'file' => 'german.wav'],
    ['lang' => Language::Portuguese, 'text' => 'Olá, como você está hoje?', 'file' => 'portuguese.wav'],
    ['lang' => Language::Japanese, 'text' => 'こんにちは、今日はお元気ですか？', 'file' => 'japanese.wav'],
    ['lang' => Language::Chinese, 'text' => '你好，你今天好吗？', 'file' => 'chinese.wav'],
];

echo "Generating speech in multiple languages...\n\n";

foreach ($texts as $item) {
    echo "→ {$item['lang']->name()}: ";

    $result = FluentVox::make()
        ->multilingual()
        ->text($item['text'])
        ->language($item['lang'])
        ->saveTo(__DIR__ . '/output/' . $item['file'])
        ->generate();

    if ($result->isSuccessful()) {
        echo "✓ {$result->getFormattedDuration()}\n";
    } else {
        echo "✗ Failed\n";
    }
}

echo "\n✓ All languages generated successfully!\n";
echo "  Check the output/ directory for the audio files.\n";
