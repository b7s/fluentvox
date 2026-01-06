<?php

declare(strict_types=1);

namespace B7s\FluentVox\Exceptions;

/**
 * Thrown when Chatterbox TTS package is not installed.
 */
class ChatterboxNotInstalledException extends FluentVoxException
{
    public static function notInstalled(): self
    {
        return new self(
            'Chatterbox TTS is not installed. ' .
            'Run: pip install chatterbox-tts'
        );
    }

    public static function versionTooLow(string $version, string $required): self
    {
        return new self(
            "Chatterbox TTS version {$version} is installed but {$required}+ is required. " .
            'Run: pip install --upgrade chatterbox-tts'
        );
    }
}
