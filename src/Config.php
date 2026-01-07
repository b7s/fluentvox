<?php

declare(strict_types=1);

namespace B7s\FluentVox;

/**
 * Configuration manager for FluentVox.
 */
final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /** @var string|null Path to the loaded config file */
    private static ?string $configPath = null;

    private function __construct() {}

    /**
     * Load configuration from file or use defaults.
     *
     * @return array<string, mixed>
     */
    public static function load(?string $configPath = null): array
    {
        if (self::$config !== null && $configPath === null) {
            return self::$config;
        }

        $paths = [
            $configPath,
            self::findProjectRoot() . '/fluentvox-config.php',
            getcwd() . '/fluentvox-config.php',
            dirname(__DIR__) . '/fluentvox-config.php',
        ];

        foreach (array_filter($paths) as $path) {
            if (file_exists($path)) {
                self::$config = require $path;
                self::$configPath = realpath($path);
                return self::$config;
            }
        }

        self::$config = self::defaults();
        return self::$config;
    }

    /**
     * Get the directory where the config file is located.
     * Useful for resolving relative paths in config values.
     */
    public static function getConfigDirectory(): ?string
    {
        self::load();
        return self::$configPath !== null ? dirname(self::$configPath) : null;
    }

    /**
     * Find the project root directory (where composer.json is located, outside vendor/).
     *
     * @return string
     */
    private static function findProjectRoot(): string
    {
        // Strategy 1: Try to find vendor/autoload.php and go up one level
        // This works when the package is installed via Composer
        $autoloadPaths = [
            __DIR__ . '/../../autoload.php',      // vendor/autoload.php when installed (vendor/b7s/fluentvox/src -> vendor/autoload.php)
            __DIR__ . '/../../../autoload.php',   // Alternative location
            getcwd() . '/vendor/autoload.php',     // From current working directory
        ];

        foreach ($autoloadPaths as $autoloadPath) {
            $realPath = realpath($autoloadPath);
            if ($realPath !== false && file_exists($realPath)) {
                $vendorDir = dirname($realPath);
                // Verify we're in vendor/ directory
                if (basename($vendorDir) === 'vendor') {
                    $projectRoot = dirname($vendorDir);
                    // Verify composer.json exists in project root
                    if (file_exists($projectRoot . '/composer.json')) {
                        return $projectRoot;
                    }
                }
            }
        }

        // Strategy 2: Search upwards from current working directory for composer.json
        // Skip directories inside vendor/
        $dir = getcwd();
        $maxDepth = 10; // Prevent infinite loops
        $depth = 0;

        while ($depth < $maxDepth) {
            // Check if composer.json exists and we're not inside vendor/
            $composerJson = $dir . '/composer.json';
            if (file_exists($composerJson)) {
                // Verify we're not inside vendor/ directory
                $normalizedPath = str_replace('\\', '/', $dir);
                if (!str_contains($normalizedPath, '/vendor/') && !str_ends_with($normalizedPath, '/vendor')) {
                    return $dir;
                }
            }

            $parent = dirname($dir);
            // Stop if we've reached the filesystem root
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
            $depth++;
        }

        // Final fallback: return current working directory
        return getcwd();
    }

    /**
     * Get a configuration value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::load();

        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = $config;

            foreach ($keys as $k) {
                if (!is_array($value) || !array_key_exists($k, $value)) {
                    return $default;
                }
                $value = $value[$k];
            }

            return $value;
        }

        return $config[$key] ?? $default;
    }

    /**
     * Reset configuration (useful for testing).
     */
    public static function reset(): void
    {
        self::$config = null;
        self::$configPath = null;
    }

    /**
     * Get default configuration values.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'python_path' => null,
            'models_path' => null,
            'default_model' => 'chatterbox',
            'device' => 'auto',
            'output_path' => null,
            'audio_format' => 'wav',
            'sample_rate' => 24000,
            'defaults' => [
                'exaggeration' => 0.5,
                'temperature' => 0.8,
                'cfg_weight' => 0.5,
                'seed' => 0,
            ],
            'timeout' => 300,
            'verbose' => false,
        ];
    }
}
