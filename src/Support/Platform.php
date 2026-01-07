<?php

declare(strict_types=1);

namespace B7s\FluentVox\Support;

use B7s\FluentVox\Enums\OperatingSystem;

/**
 * Platform detection and utilities for cross-platform support.
 */
final class Platform
{
    private static ?OperatingSystem $os = null;
    private static ?string $architecture = null;

    private function __construct() {}

    /**
     * Get the current operating system.
     */
    public static function os(): OperatingSystem
    {
        if (self::$os === null) {
            self::$os = OperatingSystem::detect();
        }

        return self::$os;
    }

    /**
     * Get the system architecture.
     */
    public static function architecture(): string
    {
        if (self::$architecture === null) {
            self::$architecture = php_uname('m');

            // Normalize architecture names
            self::$architecture = match (strtolower(self::$architecture)) {
                'x86_64', 'amd64' => 'x86_64',
                'arm64', 'aarch64' => 'arm64',
                'i386', 'i686', 'x86' => 'x86',
                default => self::$architecture,
            };
        }

        return self::$architecture;
    }

    /**
     * Check if running on Linux.
     */
    public static function isLinux(): bool
    {
        return self::os() === OperatingSystem::Linux;
    }

    /**
     * Check if running on macOS.
     */
    public static function isMacOS(): bool
    {
        return self::os() === OperatingSystem::MacOS;
    }

    /**
     * Check if running on Windows.
     */
    public static function isWindows(): bool
    {
        return self::os() === OperatingSystem::Windows;
    }

    /**
     * Check if running on Apple Silicon (M1/M2/M3, etc).
     */
    public static function isAppleSilicon(): bool
    {
        return self::isMacOS() && self::architecture() === 'arm64';
    }

    /**
     * Check if the system has NVIDIA GPU support potential.
     */
    public static function hasNvidiaGpuPotential(): bool
    {
        // Windows and Linux can have NVIDIA GPUs
        // macOS uses Metal, not CUDA
        return !self::isMacOS();
    }

    /**
     * Get the home directory.
     */
    public static function homeDirectory(): string
    {
        return self::os()->getHomeDirectory();
    }

    /**
     * Get the cache directory for FluentVox.
     */
    public static function cacheDirectory(): string
    {
        return self::os()->getCacheDirectory();
    }

    /**
     * Get the HuggingFace cache directory.
     */
    public static function huggingFaceCacheDirectory(): string
    {
        return self::os()->getHuggingFaceCacheDirectory();
    }

    /**
     * Normalize a path for the current OS.
     */
    public static function normalizePath(string $path): string
    {
        $separator = self::os()->getPathSeparator();

        // Replace both separators with the correct one
        $path = str_replace(['/', '\\'], $separator, $path);

        // Remove duplicate separators
        while (str_contains($path, $separator . $separator)) {
            $path = str_replace($separator . $separator, $separator, $path);
        }

        return $path;
    }

    /**
     * Join path segments using the correct separator.
     */
    public static function joinPath(string ...$segments): string
    {
        $separator = self::os()->getPathSeparator();
        return self::normalizePath(implode($separator, $segments));
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     */
    public static function ensureDirectory(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0755, true);
    }

    /**
     * Get platform-specific environment variables for Python.
     *
     * @return array<string, string>
     */
    public static function getPythonEnv(): array
    {
        $env = [];

        // Disable HuggingFace progress bars in non-interactive mode
        $env['HF_HUB_DISABLE_PROGRESS_BARS'] = '0';
        $env['TRANSFORMERS_VERBOSITY'] = 'warning';

        // Set cache directories
        // Note: HF_HOME should point to ~/.cache/huggingface (without /hub)
        // HuggingFace automatically appends /hub to HF_HOME
        $home = self::homeDirectory();
        $env['HF_HOME'] = match (self::os()) {
            OperatingSystem::Windows => $home . '\\AppData\\Local\\huggingface',
            default => $home . '/.cache/huggingface',
        };

        // macOS specific: Use Metal Performance Shaders
        if (self::isMacOS()) {
            $env['PYTORCH_ENABLE_MPS_FALLBACK'] = '1';
        }

        return $env;
    }

    /**
     * Get system information as array.
     *
     * @return array<string, mixed>
     */
    public static function info(): array
    {
        return [
            'os' => self::os()->value,
            'os_name' => self::os()->displayName(),
            'architecture' => self::architecture(),
            'php_version' => PHP_VERSION,
            'home_directory' => self::homeDirectory(),
            'cache_directory' => self::cacheDirectory(),
            'is_unix' => self::os()->isUnix(),
            'has_nvidia_potential' => self::hasNvidiaGpuPotential(),
            'is_apple_silicon' => self::isAppleSilicon(),
        ];
    }

    /**
     * Reset cached values (useful for testing).
     */
    public static function reset(): void
    {
        self::$os = null;
        self::$architecture = null;
    }
}
