<?php

declare(strict_types=1);

namespace B7s\FluentVox\Concerns;

use B7s\FluentVox\Exceptions\FluentVoxException;
use B7s\FluentVox\Exceptions\GenerationException;
use B7s\FluentVox\Results\GenerationResult;
use B7s\FluentVox\Support\AudioConverter;
use InvalidArgumentException;

trait ConvertsAudio
{
    /**
     * Convert the generated audio to a specific format.
     * Identified by file extension.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws GenerationException|FluentVoxException
     */
    public function convertTo(string $outputPath, array $options = [], bool $deleteOriginal = false): GenerationResult
    {
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp3' => $this->convertToMp3($outputPath, $options['bitrate'] ?? 192, $deleteOriginal),
            'm4a', 'aac' => $this->convertToM4a($outputPath, $options['bitrate'] ?? 128, $deleteOriginal),
            'ogg' => $this->convertToOgg($outputPath, $options['quality'] ?? 5, $deleteOriginal),
            'opus' => $this->convertToOpus($outputPath, $options['bitrate'] ?? 96, $deleteOriginal),
            'flac' => $this->convertToFlac($outputPath, $deleteOriginal),
            default => throw new InvalidArgumentException(
                "Unsupported audio format: .{$extension}. ".
                'Supported formats: mp3, m4a, aac, ogg, opus, flac'
            ),
        };
    }

    /**
     * @throws GenerationException
     * @throws FluentVoxException
     */
    public function convertToMp3(?string $outputPath = null, int $bitrate = 192, bool $deleteOriginal = false): GenerationResult
    {
        return $this->convertAudioFormat(
            'mp3',
            $outputPath,
            ['bitrate' => $bitrate],
            $deleteOriginal,
            fn (AudioConverter $converter, string $input, string $output) => $converter->toMp3($input, $output, $bitrate),
        );
    }

    /**
     * @throws GenerationException
     * @throws FluentVoxException
     */
    public function convertToM4a(?string $outputPath = null, int $bitrate = 128, bool $deleteOriginal = false): GenerationResult
    {
        return $this->convertAudioFormat(
            'm4a',
            $outputPath,
            ['bitrate' => $bitrate],
            $deleteOriginal,
            fn (AudioConverter $converter, string $input, string $output) => $converter->toM4a($input, $output, $bitrate),
        );
    }

    /**
     * @throws GenerationException
     * @throws FluentVoxException
     */
    public function convertToOgg(?string $outputPath = null, int $quality = 5, bool $deleteOriginal = false): GenerationResult
    {
        return $this->convertAudioFormat(
            'ogg',
            $outputPath,
            ['quality' => $quality],
            $deleteOriginal,
            fn (AudioConverter $converter, string $input, string $output) => $converter->toOgg($input, $output, $quality),
        );
    }

    /**
     * @throws GenerationException
     * @throws FluentVoxException
     */
    public function convertToOpus(?string $outputPath = null, int $bitrate = 96, bool $deleteOriginal = false): GenerationResult
    {
        return $this->convertAudioFormat(
            'opus',
            $outputPath,
            ['bitrate' => $bitrate],
            $deleteOriginal,
            fn (AudioConverter $converter, string $input, string $output) => $converter->toOpus($input, $output, $bitrate),
        );
    }

    /**
     * @throws FluentVoxException
     * @throws GenerationException
     */
    public function convertToFlac(?string $outputPath = null, bool $deleteOriginal = false): GenerationResult
    {
        return $this->convertAudioFormat(
            'flac',
            $outputPath,
            [],
            $deleteOriginal,
            fn (AudioConverter $converter, string $input, string $output) => $converter->toFlac($input, $output),
        );
    }

    /**
     * @param  array<string, mixed>  $formatMetadata
     *
     * @throws GenerationException
     */
    private function convertAudioFormat(
        string $format,
        ?string $outputPath,
        array $formatMetadata,
        bool $deleteOriginal,
        callable $conversionCallback
    ): GenerationResult {
        $result = $this->generate();

        if (! $result->isSuccessful()) {
            return $result;
        }

        $outputPath = $outputPath ?? str_replace('.wav', '.'.$format, $result->outputPath);
        $converter = new AudioConverter($this->timeout);

        if ($conversionCallback($converter, $result->outputPath, $outputPath)) {
            if ($deleteOriginal && file_exists($result->outputPath)) {
                unlink($result->outputPath);
            }

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $result->text,
                sampleRate: $result->sampleRate,
                duration: $result->duration,
                metadata: array_merge($result->metadata, array_merge(['converted_to' => $format], $formatMetadata)),
            );
        }

        return GenerationResult::failure('Failed to convert to '.strtoupper($format));
    }
}
