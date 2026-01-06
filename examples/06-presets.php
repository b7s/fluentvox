<?php

/**
 * Example 6: Using Presets for Common Scenarios
 *
 * This example demonstrates the built-in presets that combine
 * multiple settings optimized for specific use cases.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

echo "Generating speech with different presets...\n\n";

// 1. Narration preset (audiobooks, documentation)
echo "→ Narration preset: ";
$result = FluentVox::make()
    ->text('In the beginning, there was nothing but darkness. Then, a spark of light appeared in the void.')
    ->forNarration()
    ->saveTo(__DIR__ . '/output/preset-narration.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 2. Dialogue preset (character voices, conversations)
echo "→ Dialogue preset: ";
$result = FluentVox::make()
    ->text('Hey! Did you hear about the new project? It\'s going to be amazing!')
    ->forDialogue()
    ->saveTo(__DIR__ . '/output/preset-dialogue.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 3. Voice agent preset (chatbots, assistants)
echo "→ Voice agent preset: ";
$result = FluentVox::make()
    ->text('I can help you with that. Let me check the information for you.')
    ->forVoiceAgent()
    ->saveTo(__DIR__ . '/output/preset-voice-agent.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

// 4. Audiobook preset (long-form storytelling)
echo "→ Audiobook preset: ";
$result = FluentVox::make()
    ->text('The old mansion stood atop the hill, its windows dark and foreboding. No one had lived there for decades.')
    ->forAudiobook()
    ->saveTo(__DIR__ . '/output/preset-audiobook.wav')
    ->generate();
echo $result->isSuccessful() ? "✓\n" : "✗\n";

echo "\n✓ All presets generated!\n";
echo "  Each preset is optimized for its specific use case.\n";
