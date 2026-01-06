<?php

declare(strict_types=1);

namespace B7s\FluentVox\Results;

/**
 * Result of a TTS generation operation.
 */
final class GenerationResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $outputPath,
        public readonly ?string $text,
        public readonly ?int $sampleRate,
        public readonly ?float $duration,
        public readonly ?string $error = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a successful result.
     *
     * @param array<string, mixed> $metadata
     */
    public static function success(
        string $outputPath,
        string $text,
        int $sampleRate,
        float $duration,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            outputPath: $outputPath,
            text: $text,
            sampleRate: $sampleRate,
            duration: $duration,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(string $error): self
    {
        return new self(
            success: false,
            outputPath: null,
            text: null,
            sampleRate: null,
            duration: null,
            error: $error,
        );
    }

    /**
     * Check if generation was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the output file path.
     */
    public function getPath(): ?string
    {
        return $this->outputPath;
    }

    /**
     * Get the audio duration in seconds.
     */
    public function getDuration(): ?float
    {
        return $this->duration;
    }

    /**
     * Get formatted duration (MM:SS).
     */
    public function getFormattedDuration(): ?string
    {
        if ($this->duration === null) {
            return null;
        }

        $minutes = (int) floor($this->duration / 60);
        $seconds = fmod($this->duration, 60);

        return sprintf('%02d:%05.2f', $minutes, $seconds);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'output_path' => $this->outputPath,
            'text' => $this->text,
            'sample_rate' => $this->sampleRate,
            'duration' => $this->duration,
            'duration_formatted' => $this->getFormattedDuration(),
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }
}
