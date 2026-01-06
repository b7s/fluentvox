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
            getcwd() . '/fluentvox-config.php',
            dirname(__DIR__) . '/fluentvox-config.php',
        ];

        foreach (array_filter($paths) as $path) {
            if (file_exists($path)) {
                self::$config = require $path;
                return self::$config;
            }
        }

        self::$config = self::defaults();
        return self::$config;
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
