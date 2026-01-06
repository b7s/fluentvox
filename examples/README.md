# FluentVox Examples

This directory contains practical examples demonstrating various features and use cases of FluentVox.

## Prerequisites

Before running the examples, make sure you have:

1. Installed FluentVox: `composer require b7s/fluentvox`
2. Installed dependencies: `vendor/bin/fluentvox install`
3. Verified installation: `vendor/bin/fluentvox doctor`

## Running Examples

Navigate to the examples directory and run any example:

```bash
cd examples
php 01-basic-usage.php
```

Generated audio files will be saved to the `examples/output/` directory.

## Examples Overview

### Basic Examples

- **01-basic-usage.php** - Simple text-to-speech generation
- **02-voice-cloning.php** - Clone a voice from reference audio
- **03-multilingual.php** - Generate speech in multiple languages

### Expression & Control

- **04-expression-control.php** - Control emotional intensity
- **05-pace-control.php** - Adjust speaking speed and rhythm
- **06-presets.php** - Use built-in presets for common scenarios
- **07-reproducible-output.php** - Generate identical output with seeds

### Advanced Usage

- **08-batch-processing.php** - Process multiple texts efficiently
- **09-progress-monitoring.php** - Monitor generation progress
- **10-advanced-configuration.php** - Fine-tune all parameters
- **11-error-handling.php** - Proper error handling and validation

### Real-World Applications

- **12-real-world-chatbot.php** - Chatbot voice response generator
- **13-audio-conversion.php** - Convert audio to multiple formats (MP3, M4A, OGG, Opus, FLAC)

## Example Categories

### For Beginners

Start with examples 1-3 to understand the basics.

### For Content Creators

Check examples 4-6 for expression control and presets.

### For Developers

Examples 8-11 cover batch processing, monitoring, and error handling.

### For Production Use

Example 12 demonstrates a real-world integration pattern.

## Tips

- Always check `$result->isSuccessful()` before using the output
- Use presets (`forNarration()`, `forDialogue()`, etc.) as starting points
- Monitor progress for long texts using `onProgress()`
- Handle errors gracefully with try-catch blocks
- Use seeds for reproducible results in testing

## Output Directory

All examples save audio files to `examples/output/`. This directory will be created automatically if it doesn't exist.

## Need Help?

- Check the main README.md for full API documentation
- Run `vendor/bin/fluentvox doctor` to diagnose issues
- Review error messages carefully - they usually indicate the problem

## Contributing

Have a useful example? Feel free to contribute by adding it to this directory!
