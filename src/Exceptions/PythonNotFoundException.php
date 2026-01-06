<?php

declare(strict_types=1);

namespace B7s\FluentVox\Exceptions;

/**
 * Thrown when Python is not found or not properly configured.
 */
class PythonNotFoundException extends FluentVoxException
{
    public static function notInstalled(): self
    {
        return new self(
            'Python 3.10+ is required but was not found. ' .
            'Please install Python and ensure it is in your PATH.'
        );
    }

    public static function versionTooLow(string $version): self
    {
        return new self(
            "Python 3.10+ is required but version {$version} was found. " .
            'Please upgrade your Python installation.'
        );
    }

    public static function customPathInvalid(string $path): self
    {
        return new self(
            "The configured Python path '{$path}' is not valid or executable."
        );
    }
}
