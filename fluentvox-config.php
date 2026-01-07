<?php

/**
 * FluentVox Configuration
 *
 * Copy this file to your project root and customize as needed.
 */

return [
    // Python executable path (null = auto-detect)
    // Pass full path to python bin: /path/to/bin/python
    'python_path' => null,

    // Directory where models will be stored
    'models_path' => null, // null = ~/.cache/huggingface/hub

    // Default model to use
    'default_model' => 'chatterbox', // Options: 'chatterbox', 'chatterbox-turbo', 'chatterbox-multilingual'

    // Default device for inference
    'device' => 'auto', // Options: 'auto', 'cuda', 'cpu'

    // Default output directory for generated audio
    'output_path' => null, // null = current working directory

    // Default audio format
    'audio_format' => 'wav',

    // Default sample rate
    'sample_rate' => 24000,

    // Generation defaults
    'defaults' => [
        'exaggeration' => 0.5,    // Controls expressiveness (0.25-2.0, neutral=0.5)
        'temperature' => 0.8,     // Controls randomness (0.05-5.0)
        'cfg_weight' => 0.5,      // CFG/Pace weight (0.2-1.0)
        'seed' => 0,              // Random seed (0 = random)
    ],

    // Process timeout in seconds
    'timeout' => 300,

    // Enable verbose output
    'verbose' => false,
];
