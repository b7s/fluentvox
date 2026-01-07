<?php

declare(strict_types=1);

namespace B7s\FluentVox\Support;

use B7s\FluentVox\Exceptions\FluentVoxException;
use Symfony\Component\Process\Process;

/**
 * Audio format converter using FFmpeg.
 */
final class AudioConverter
{
    private ?string $ffmpegPath = null;

    public function __construct(
        private readonly int $timeout = 300,
    ) {}

    /**
     * Convert audio file to another format.
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination file path
     * @param array<string, mixed> $options FFmpeg options
     */
    public function convert(string $inputPath, string $outputPath, array $options = []): bool
    {
        if (!file_exists($inputPath)) {
            throw new FluentVoxException("Input file not found: {$inputPath}");
        }

        $ffmpeg = $this->getFfmpegPath();
        $command = $this->buildCommand($ffmpeg, $inputPath, $outputPath, $options);

        $process = new Process($command, timeout: $this->timeout);
        $process->run();

        return $process->isSuccessful() && file_exists($outputPath);
    }

    /**
     * Convert to MP3 format.
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination MP3 file
     * @param int $bitrate Bitrate in kbps (default: 192)
     */
    public function toMp3(string $inputPath, string $outputPath, int $bitrate = 192): bool
    {
        return $this->convert($inputPath, $outputPath, [
            'codec:a' => 'libmp3lame',
            'b:a' => "{$bitrate}k",
            'ar' => '44100',
        ]);
    }

    /**
     * Convert to AAC/M4A format.
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination M4A file
     * @param int $bitrate Bitrate in kbps (default: 128)
     */
    public function toM4a(string $inputPath, string $outputPath, int $bitrate = 128): bool
    {
        return $this->convert($inputPath, $outputPath, [
            'codec:a' => 'aac',
            'b:a' => "{$bitrate}k",
            'ar' => '44100',
        ]);
    }

    /**
     * Convert to OGG Vorbis format.
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination OGG file
     * @param int $quality Quality level 0-10 (default: 5)
     */
    public function toOgg(string $inputPath, string $outputPath, int $quality = 5): bool
    {
        return $this->convert($inputPath, $outputPath, [
            'codec:a' => 'libvorbis',
            'q:a' => (string)$quality,
        ]);
    }

    /**
     * Convert to Opus format.
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination Opus file
     * @param int $bitrate Bitrate in kbps (default: 96)
     */
    public function toOpus(string $inputPath, string $outputPath, int $bitrate = 96): bool
    {
        return $this->convert($inputPath, $outputPath, [
            'codec:a' => 'libopus',
            'b:a' => "{$bitrate}k",
        ]);
    }

    /**
     * Convert to FLAC format (lossless).
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination FLAC file
     */
    public function toFlac(string $inputPath, string $outputPath): bool
    {
        return $this->convert($inputPath, $outputPath, [
            'codec:a' => 'flac',
        ]);
    }

    /**
     * Get audio file information.
     *
     * @return array{duration: float, sample_rate: int, channels: int, codec: string, bitrate: int}|null
     */
    public function getInfo(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $ffprobe = $this->getFfprobePath();

        $command = [
            $ffprobe,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $filePath,
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $data = json_decode($process->getOutput(), true);
        if (!is_array($data)) {
            return null;
        }

        $stream = $data['streams'][0] ?? null;
        $format = $data['format'] ?? null;

        if (!$stream || !$format) {
            return null;
        }

        return [
            'duration' => (float)($format['duration'] ?? 0),
            'sample_rate' => (int)($stream['sample_rate'] ?? 0),
            'channels' => (int)($stream['channels'] ?? 0),
            'codec' => (string)($stream['codec_name'] ?? 'unknown'),
            'bitrate' => (int)($format['bit_rate'] ?? 0),
        ];
    }

    /**
     * Get FFmpeg executable path.
     */
    private function getFfmpegPath(): string
    {
        if ($this->ffmpegPath !== null) {
            return $this->ffmpegPath;
        }

        // Try system PATH first
        $candidates = Platform::isWindows()
            ? ['ffmpeg.exe', 'ffmpeg']
            : ['ffmpeg'];

        foreach ($candidates as $candidate) {
            $process = new Process([$candidate, '-version']);
            $process->run();

            if ($process->isSuccessful()) {
                $this->ffmpegPath = $candidate;
                return $this->ffmpegPath;
            }
        }

        // Check local installation in bin directory
        $localPaths = [
            __DIR__ . '/../../bin/ffmpeg',
            __DIR__ . '/../../vendor/bin/ffmpeg',
        ];

        if (Platform::isWindows()) {
            $localPaths = array_map(fn($path) => $path . '.exe', $localPaths);
        }

        foreach ($localPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                $this->ffmpegPath = $path;
                return $this->ffmpegPath;
            }
        }

        $installHint = $this->getFfmpegInstallHint();
        throw new FluentVoxException(
            "FFmpeg not found. Please install FFmpeg or ensure it is in your PATH. {$installHint}"
        );
    }

    /**
     * Get platform-specific FFmpeg installation hint.
     */
    private function getFfmpegInstallHint(): string
    {
        return match (Platform::os()) {
            \B7s\FluentVox\Enums\OperatingSystem::Windows => 'Download from https://ffmpeg.org or run: winget install FFmpeg',
            \B7s\FluentVox\Enums\OperatingSystem::MacOS => 'Run: brew install ffmpeg',
            \B7s\FluentVox\Enums\OperatingSystem::Linux => 'Run: sudo apt install ffmpeg (Ubuntu/Debian) or sudo dnf install ffmpeg (Fedora)',
        };
    }

    /**
     * Get FFprobe executable path.
     */
    private function getFfprobePath(): string
    {
        // Try system PATH first
        $candidates = Platform::isWindows()
            ? ['ffprobe.exe', 'ffprobe']
            : ['ffprobe'];

        foreach ($candidates as $candidate) {
            $process = new Process([$candidate, '-version']);
            $process->run();

            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        // Check local installation in bin directory
        $localPaths = [
            __DIR__ . '/../../bin/ffprobe',
            __DIR__ . '/../../vendor/bin/ffprobe',
        ];

        if (Platform::isWindows()) {
            $localPaths = array_map(fn($path) => $path . '.exe', $localPaths);
        }

        foreach ($localPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        $installHint = $this->getFfmpegInstallHint();
        throw new FluentVoxException(
            "FFprobe not found. Please install FFmpeg or ensure it is in your PATH. {$installHint}"
        );
    }

    /**
     * Build FFmpeg command.
     *
     * @param array<string, mixed> $options
     * @return array<string>
     */
    private function buildCommand(string $ffmpeg, string $input, string $output, array $options): array
    {
        $command = [
            $ffmpeg,
            '-i', $input,
            '-y', // Overwrite output file
        ];

        foreach ($options as $key => $value) {
            $command[] = "-{$key}";
            $command[] = (string)$value;
        }

        $command[] = $output;

        return $command;
    }
}
