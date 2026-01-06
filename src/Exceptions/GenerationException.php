<?php

declare(strict_types=1);

namespace B7s\FluentVox\Exceptions;

/**
 * Thrown when audio generation fails.
 */
class GenerationException extends FluentVoxException
{
    public static function failed(string $reason): self
    {
        return new self("Audio generation failed: {$reason}");
    }

    public static function timeout(int $seconds): self
    {
        return new self(
            "Audio generation timed out after {$seconds} seconds. " .
            'Try reducing text length or increasing timeout.'
        );
    }

    public static function invalidText(): self
    {
        return new self('Text input is required for audio generation.');
    }

    public static function textTooLong(int $length, int $max): self
    {
        return new self(
            "Text length ({$length} chars) exceeds maximum ({$max} chars). " .
            'Please split your text into smaller chunks.'
        );
    }

    public static function invalidAudioPrompt(string $path): self
    {
        return new self(
            "Audio prompt file not found or invalid: {$path}"
        );
    }
}
