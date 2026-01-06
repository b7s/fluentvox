<?php

declare(strict_types=1);

namespace B7s\FluentVox\Enums;

/**
 * Supported operating systems.
 */
enum OperatingSystem: string
{
    case Linux = 'linux';
    case MacOS = 'darwin';
    case Windows = 'windows';

    /**
     * Detect the current operating system.
     */
    public static function detect(): self
    {
        return match (true) {
            str_contains(strtolower(PHP_OS_FAMILY), 'win') => self::Windows,
            str_contains(strtolower(PHP_OS), 'darwin') => self::MacOS,
            default => self::Linux,
        };
    }

    /**
     * Get the home directory path.
     */
    public function getHomeDirectory(): string
    {
        return match ($this) {
            self::Windows => $this->getWindowsHomeDirectory(),
            default => $_SERVER['HOME'] ?? '/tmp',
        };
    }

    /**
     * Get Windows home directory with fallbacks.
     */
    private function getWindowsHomeDirectory(): string
    {
        if (isset($_SERVER['USERPROFILE'])) {
            return $_SERVER['USERPROFILE'];
        }

        if (isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            return $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
        }

        return 'C:\\Users\\' . get_current_user();
    }

    /**
     * Get the default cache directory for models.
     */
    public function getCacheDirectory(): string
    {
        $home = $this->getHomeDirectory();

        return match ($this) {
            self::Windows => $home . '\\AppData\\Local\\fluentvox',
            self::MacOS => $home . '/Library/Caches/fluentvox',
            self::Linux => $home . '/.cache/fluentvox',
        };
    }

    /**
     * Get the HuggingFace cache directory.
     */
    public function getHuggingFaceCacheDirectory(): string
    {
        $home = $this->getHomeDirectory();

        return match ($this) {
            self::Windows => $home . '\\AppData\\Local\\huggingface\\hub',
            default => $home . '/.cache/huggingface/hub',
        };
    }

    /**
     * Get the path separator for this OS.
     */
    public function getPathSeparator(): string
    {
        return match ($this) {
            self::Windows => '\\',
            default => '/',
        };
    }

    /**
     * Get the environment path separator.
     */
    public function getEnvPathSeparator(): string
    {
        return match ($this) {
            self::Windows => ';',
            default => ':',
        };
    }

    /**
     * Get Python executable candidates for this OS.
     *
     * @return array<int, string>
     */
    public function getPythonCandidates(): array
    {
        return match ($this) {
            self::Windows => [
                'python',
                'python3',
                'py -3',
                'C:\\Python311\\python.exe',
                'C:\\Python310\\python.exe',
                'C:\\Python312\\python.exe',
                'C:\\Python313\\python.exe',
            ],
            default => [
                'python3',
                'python',
                'python3.11',
                'python3.10',
                'python3.12',
                'python3.13',
                '/usr/bin/python3',
                '/usr/local/bin/python3',
            ],
        };
    }

    /**
     * Get pip executable candidates for this OS.
     *
     * @return array<int, string>
     */
    public function getPipCandidates(): array
    {
        return match ($this) {
            self::Windows => ['pip', 'pip3', 'py -m pip'],
            default => ['pip3', 'pip', 'python3 -m pip', 'python -m pip'],
        };
    }

    /**
     * Check if this is a Unix-like system.
     */
    public function isUnix(): bool
    {
        return $this !== self::Windows;
    }

    /**
     * Get the null device path.
     */
    public function getNullDevice(): string
    {
        return match ($this) {
            self::Windows => 'NUL',
            default => '/dev/null',
        };
    }

    /**
     * Get the display name.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Linux => 'Linux',
            self::MacOS => 'macOS',
            self::Windows => 'Windows',
        };
    }
}
