<?php

/**
 * Example 12: Real-World Chatbot Integration
 *
 * This example simulates a chatbot that generates voice responses
 * for user queries, demonstrating a practical use case.
 */

require __DIR__ . '/../vendor/autoload.php';

use B7s\FluentVox\FluentVox;

// Simulate chatbot conversation
$conversation = [
    ['user' => 'Hello!', 'bot' => 'Hello! How can I help you today?'],
    ['user' => 'What\'s the weather like?', 'bot' => 'I can help you check the weather. What city are you interested in?'],
    ['user' => 'New York', 'bot' => 'The weather in New York is sunny with a high of 75 degrees Fahrenheit.'],
];

echo "Chatbot Voice Response Generator\n";
echo str_repeat('=', 50) . "\n\n";

foreach ($conversation as $index => $exchange) {
    $turnNum = $index + 1;
    
    echo "Turn {$turnNum}:\n";
    echo "  User: {$exchange['user']}\n";
    echo "  Bot: {$exchange['bot']}\n";
    echo "  → Generating voice response: ";
    
    $result = FluentVox::make()
        ->text($exchange['bot'])
        ->forVoiceAgent()
        ->saveTo(__DIR__ . "/output/chatbot-turn-{$turnNum}.wav")
        ->generate();
    
    echo $result->isSuccessful() ? "✓\n\n" : "✗\n\n";
}

echo "✓ Chatbot conversation complete!\n";
