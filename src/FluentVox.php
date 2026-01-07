<?php

declare(strict_types=1);

namespace B7s\FluentVox;

use B7s\FluentVox\Enums\Device;
use B7s\FluentVox\Enums\Language;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Exceptions\GenerationException;
use B7s\FluentVox\Results\GenerationResult;
use B7s\FluentVox\Support\ModelManager;
use B7s\FluentVox\Support\PythonRunner;
use B7s\FluentVox\Support\RequirementsChecker;

/**
 * FluentVox - Fluent PHP wrapper for Chatterbox TTS.
 *
 * @example
 * // Basic usage
 * $result = FluentVox::make()
 *     ->text('Hello, world!')
 *     ->generate();
 *
 * // With voice cloning
 * $result = FluentVox::make()
 *     ->text('Hello, world!')
 *     ->voiceFrom('/path/to/reference.wav')
 *     ->generate();
 *
 * // Multilingual
 * $result = FluentVox::make()
 *     ->multilingual()
 *     ->text('Bonjour le monde!')
 *     ->language(Language::French)
 *     ->generate();
 */
class FluentVox
{
    private ?string $text = null;
    private ?string $audioPromptPath = null;
    private ?string $outputPath = null;
    private Model $model;
    private Device $device;
    private ?Language $language = null;

    // Generation parameters
    private float $exaggeration;
    private float $temperature;
    private float $cfgWeight;
    private int $seed;
    private bool $vadTrim = false;

    // Process settings
    private int $timeout;
    private bool $verbose;
    /** @var callable|null */
    private mixed $progressCallback = null;

    // Support classes
    private PythonRunner $python;
    private ModelManager $modelManager;

    public function __construct()
    {
        $this->model = Model::from(Config::get('default_model', 'chatterbox'));
        $this->device = Device::from(Config::get('device', 'auto'));
        $this->exaggeration = (float) Config::get('defaults.exaggeration', 0.5);
        $this->temperature = (float) Config::get('defaults.temperature', 0.8);
        $this->cfgWeight = (float) Config::get('defaults.cfg_weight', 0.5);
        $this->seed = (int) Config::get('defaults.seed', 0);
        $this->timeout = (int) Config::get('timeout', 300);
        $this->verbose = (bool) Config::get('verbose', false);

        $this->python = new PythonRunner($this->timeout, $this->verbose);
        $this->modelManager = new ModelManager($this->python);
    }

    /**
     * Create a new FluentVox instance.
     */
    public static function make(): self
    {
        return new self();
    }

    // =========================================================================
    // TEXT INPUT
    // =========================================================================

    /**
     * Set the text to synthesize into speech.
     *
     * @param string $text The text content to convert to audio (max 300 chars recommended)
     */
    public function text(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    // =========================================================================
    // MODEL SELECTION
    // =========================================================================

    /**
     * Use the standard Chatterbox model (English, 500M params).
     * Best for general zero-shot TTS with creative controls.
     */
    public function standard(): self
    {
        $this->model = Model::Chatterbox;
        return $this;
    }

    /**
     * Use the Chatterbox Turbo model (English, 350M params).
     * Faster inference with paralinguistic tags support ([laugh], [cough], etc).
     */
    public function turbo(): self
    {
        $this->model = Model::ChatterboxTurbo;
        return $this;
    }

    /**
     * Use the Chatterbox Multilingual model (23+ languages, 500M params).
     * Supports Arabic, Chinese, French, German, Japanese, Spanish, and more.
     */
    public function multilingual(): self
    {
        $this->model = Model::ChatterboxMultilingual;
        return $this;
    }

    /**
     * Set a specific model to use.
     *
     * @param Model $model The model enum value
     */
    public function model(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    // =========================================================================
    // VOICE CLONING
    // =========================================================================

    /**
     * Clone voice characteristics from a reference audio file.
     * The generated speech will mimic the speaker's voice and style.
     *
     * @param string $audioPath Path to the reference audio file (WAV, MP3, FLAC)
     */
    public function voiceFrom(string $audioPath): self
    {
        if (!file_exists($audioPath)) {
            throw GenerationException::invalidAudioPrompt($audioPath);
        }

        $this->audioPromptPath = realpath($audioPath);
        return $this;
    }

    /**
     * Alias for voiceFrom() - clone voice from reference audio.
     *
     * @param string $audioPath Path to the reference audio file
     */
    public function cloneVoice(string $audioPath): self
    {
        return $this->voiceFrom($audioPath);
    }

    /**
     * Use the default voice (no voice cloning).
     */
    public function defaultVoice(): self
    {
        $this->audioPromptPath = null;
        return $this;
    }

    // =========================================================================
    // LANGUAGE (Multilingual model only)
    // =========================================================================

    /**
     * Set the target language for synthesis (multilingual model only).
     *
     * @param Language $language The target language
     */
    public function language(Language $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Synthesize in English.
     */
    public function english(): self
    {
        return $this->language(Language::English);
    }

    /**
     * Synthesize in French.
     */
    public function french(): self
    {
        return $this->language(Language::French);
    }

    /**
     * Synthesize in Spanish.
     */
    public function spanish(): self
    {
        return $this->language(Language::Spanish);
    }

    /**
     * Synthesize in German.
     */
    public function german(): self
    {
        return $this->language(Language::German);
    }

    /**
     * Synthesize in Portuguese.
     */
    public function portuguese(): self
    {
        return $this->language(Language::Portuguese);
    }

    /**
     * Synthesize in Japanese.
     */
    public function japanese(): self
    {
        return $this->language(Language::Japanese);
    }

    /**
     * Synthesize in Chinese.
     */
    public function chinese(): self
    {
        return $this->language(Language::Chinese);
    }

    // =========================================================================
    // EXPRESSION CONTROLS
    // =========================================================================

    /**
     * Control the expressiveness/emotion intensity of the speech.
     * Higher values produce more dramatic, expressive speech.
     *
     * @param float $value Exaggeration level (0.25-2.0, neutral=0.5)
     */
    public function exaggeration(float $value): self
    {
        $this->exaggeration = max(0.25, min(2.0, $value));
        return $this;
    }

    /**
     * Use neutral expression (exaggeration = 0.5).
     */
    public function neutral(): self
    {
        return $this->exaggeration(0.5);
    }

    /**
     * Use expressive/dramatic speech (exaggeration = 0.7).
     */
    public function expressive(): self
    {
        return $this->exaggeration(0.7);
    }

    /**
     * Use highly dramatic speech (exaggeration = 1.0).
     */
    public function dramatic(): self
    {
        return $this->exaggeration(1.0);
    }

    /**
     * Use subtle, understated expression (exaggeration = 0.3).
     */
    public function subtle(): self
    {
        return $this->exaggeration(0.3);
    }

    // =========================================================================
    // PACE/CFG CONTROLS
    // =========================================================================

    /**
     * Control the rhythm and pacing of speech.
     * Lower values = slower, more deliberate. Higher values = faster pacing.
     *
     * @param float $value CFG weight (0.2-1.0, default=0.5)
     */
    public function cfgWeight(float $value): self
    {
        $this->cfgWeight = max(0.2, min(1.0, $value));
        return $this;
    }

    /**
     * Alias for cfgWeight() - control speech pacing.
     *
     * @param float $value Pace value (0.2-1.0)
     */
    public function pace(float $value): self
    {
        return $this->cfgWeight($value);
    }

    /**
     * Use slow, deliberate pacing (cfg = 0.3).
     */
    public function slow(): self
    {
        return $this->cfgWeight(0.3);
    }

    /**
     * Use normal pacing (cfg = 0.5).
     */
    public function normalPace(): self
    {
        return $this->cfgWeight(0.5);
    }

    /**
     * Use fast pacing (cfg = 0.7).
     */
    public function fast(): self
    {
        return $this->cfgWeight(0.7);
    }

    // =========================================================================
    // RANDOMNESS CONTROLS
    // =========================================================================

    /**
     * Control the randomness/creativity of generation.
     * Higher values produce more varied output.
     *
     * @param float $value Temperature (0.05-5.0, default=0.8)
     */
    public function temperature(float $value): self
    {
        $this->temperature = max(0.05, min(5.0, $value));
        return $this;
    }

    /**
     * Use deterministic generation (low temperature = 0.3).
     */
    public function deterministic(): self
    {
        return $this->temperature(0.3);
    }

    /**
     * Use creative/varied generation (high temperature = 1.2).
     */
    public function creative(): self
    {
        return $this->temperature(1.2);
    }

    /**
     * Set a random seed for reproducible results.
     * Use 0 for random generation each time.
     *
     * @param int $seed The random seed (0 = random)
     */
    public function seed(int $seed): self
    {
        $this->seed = $seed;
        return $this;
    }

    // =========================================================================
    // AUDIO PROCESSING
    // =========================================================================

    /**
     * Enable Voice Activity Detection trimming on reference audio.
     * Removes silence from the beginning/end of the reference clip.
     */
    public function trimSilence(): self
    {
        $this->vadTrim = true;
        return $this;
    }

    /**
     * Disable VAD trimming (default).
     */
    public function keepSilence(): self
    {
        $this->vadTrim = false;
        return $this;
    }

    // =========================================================================
    // DEVICE SELECTION
    // =========================================================================

    /**
     * Use CUDA GPU for inference (faster, requires NVIDIA GPU).
     * Best for Linux/Windows with NVIDIA graphics cards.
     */
    public function cuda(): self
    {
        $this->device = Device::Cuda;
        return $this;
    }

    /**
     * Use Apple Metal Performance Shaders (macOS only).
     * Best for Apple Silicon Macs (M1/M2/M3).
     */
    public function mps(): self
    {
        $this->device = Device::Mps;
        return $this;
    }

    /**
     * Use CPU for inference (slower but always available).
     */
    public function cpu(): self
    {
        $this->device = Device::Cpu;
        return $this;
    }

    /**
     * Auto-detect best available device (default).
     * Checks CUDA → MPS → CPU in order.
     */
    public function autoDevice(): self
    {
        $this->device = Device::Auto;
        return $this;
    }

    /**
     * Set a specific device.
     *
     * @param Device $device The device to use
     */
    public function device(Device $device): self
    {
        $this->device = $device;
        return $this;
    }

    // =========================================================================
    // OUTPUT CONFIGURATION
    // =========================================================================

    /**
     * Set the output file path for the generated audio.
     *
     * @param string $path Full path including filename (e.g., '/output/speech.wav')
     */
    public function saveTo(string $path): self
    {
        $this->outputPath = $path;
        return $this;
    }

    /**
     * Alias for saveTo() - set output path.
     *
     * @param string $path Output file path
     */
    public function output(string $path): self
    {
        return $this->saveTo($path);
    }

    // =========================================================================
    // PROCESS CONFIGURATION
    // =========================================================================

    /**
     * Set the process timeout in seconds.
     *
     * @param int $seconds Timeout in seconds (0 = no timeout)
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        $this->python = new PythonRunner($this->timeout, $this->verbose);
        return $this;
    }

    /**
     * Enable verbose output for debugging.
     */
    public function verbose(): self
    {
        $this->verbose = true;
        $this->python = new PythonRunner($this->timeout, $this->verbose);
        return $this;
    }

    /**
     * Set a callback for progress updates during generation.
     *
     * @param callable $callback Function receiving (string $output, bool $isError)
     */
    public function onProgress(callable $callback): self
    {
        $this->progressCallback = $callback;
        return $this;
    }

    // =========================================================================
    // PRESETS
    // =========================================================================

    /**
     * Preset for narration: clear, neutral, consistent.
     */
    public function forNarration(): self
    {
        return $this
            ->neutral()
            ->normalPace()
            ->temperature(0.6);
    }

    /**
     * Preset for dialogue: expressive, varied.
     */
    public function forDialogue(): self
    {
        return $this
            ->expressive()
            ->normalPace()
            ->temperature(0.9);
    }

    /**
     * Preset for voice agents: fast, clear, low latency.
     */
    public function forVoiceAgent(): self
    {
        return $this
            ->turbo()
            ->neutral()
            ->fast()
            ->deterministic();
    }

    /**
     * Preset for audiobooks: dramatic, varied pacing.
     */
    public function forAudiobook(): self
    {
        return $this
            ->dramatic()
            ->slow()
            ->creative();
    }

    // =========================================================================
    // EXECUTION
    // =========================================================================

    /**
     * Generate the audio file from the configured text.
     *
     * @return GenerationResult The result containing output path and metadata
     * @throws GenerationException If generation fails
     */
    public function generate(): GenerationResult
    {
        $this->validate();

        // Ensure model is available
        $this->modelManager->ensureModel($this->model, $this->progressCallback);

        // Generate output path if not set
        $outputPath = $this->outputPath ?? $this->generateOutputPath();

        // Build and execute Python script
        $script = $this->buildGenerationScript($outputPath);

        try {
            $output = $this->python->execute($script, $this->progressCallback);

            // Parse output for metadata
            $metadata = $this->parseOutput($output);

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $this->text,
                sampleRate: $metadata['sample_rate'] ?? 24000,
                duration: $metadata['duration'] ?? 0.0,
                metadata: [
                    'model' => $this->model->value,
                    'device' => $this->device->value,
                    'exaggeration' => $this->exaggeration,
                    'temperature' => $this->temperature,
                    'cfg_weight' => $this->cfgWeight,
                    'seed' => $this->seed,
                    'language' => $this->language?->value,
                ],
            );
        } catch (\Throwable $e) {
            throw GenerationException::failed($e->getMessage());
        }
    }

    /**
     * Generate audio and return the raw WAV data as a string.
     *
     * @return string Raw audio data
     */
    public function generateRaw(): string
    {
        $tempPath = sys_get_temp_dir() . '/fluentvox_' . uniqid() . '.wav';
        $this->outputPath = $tempPath;

        try {
            $this->generate();
            $data = file_get_contents($tempPath);
            return $data !== false ? $data : '';
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    // =========================================================================
    // VALIDATION & HELPERS
    // =========================================================================

    /**
     * Validate the current configuration before generation.
     */
    private function validate(): void
    {
        if (empty($this->text)) {
            throw GenerationException::invalidText();
        }

        if ($this->model->isMultilingual() && $this->language === null) {
            $this->language = Language::English;
        }

        if ($this->audioPromptPath !== null && !file_exists($this->audioPromptPath)) {
            throw GenerationException::invalidAudioPrompt($this->audioPromptPath);
        }
    }

    /**
     * Generate a default output path.
     */
    private function generateOutputPath(): string
    {
        $basePath = Config::get('output_path') ?? getcwd();
        $format = Config::get('audio_format', 'wav');
        $filename = 'fluentvox_' . date('Ymd_His') . '_' . substr(md5($this->text), 0, 8);

        return "{$basePath}/{$filename}.{$format}";
    }

    /**
     * Build the Python generation script.
     */
    private function buildGenerationScript(string $outputPath): string
    {
        $import = $this->model->pythonImport();
        $className = $this->model->pythonClass();
        $deviceCode = $this->device->toPython();
        $text = addslashes($this->text);

        $generateArgs = [
            "exaggeration={$this->exaggeration}",
            "temperature={$this->temperature}",
            "cfg_weight={$this->cfgWeight}",
        ];

        if ($this->vadTrim) {
            $generateArgs[] = 'vad_trim=True';
        }

        if ($this->audioPromptPath !== null) {
            $audioPath = addslashes($this->audioPromptPath);
            $generateArgs[] = "audio_prompt_path=\"{$audioPath}\"";
        }

        if ($this->model->isMultilingual() && $this->language !== null) {
            $generateArgs[] = "language_id=\"{$this->language->value}\"";
        }

        $generateArgsStr = implode(', ', $generateArgs);

        $seedCode = '';
        if ($this->seed !== 0) {
            $seedCode = <<<PYTHON

import random
import numpy as np
torch.manual_seed({$this->seed})
if device == "cuda":
    torch.cuda.manual_seed({$this->seed})
    torch.cuda.manual_seed_all({$this->seed})
random.seed({$this->seed})
np.random.seed({$this->seed})
PYTHON;
        }

        return <<<PYTHON
import torch
import torchaudio as ta
import json
import sys
import warnings

# Suppress deprecation warnings
warnings.filterwarnings("ignore", category=FutureWarning)
warnings.filterwarnings("ignore", category=UserWarning)

# Monkey-patch torch.load to handle:
# 1. weights_only compatibility issue (PyTorch 2.6+)
# 2. CUDA tensors on CPU-only machines (map_location)
_original_torch_load = torch.load

def _patched_torch_load(f, *args, **kwargs):
    # Always set map_location to CPU if CUDA is not available
    if not torch.cuda.is_available() and 'map_location' not in kwargs:
        kwargs['map_location'] = 'cpu'
    
    try:
        return _original_torch_load(f, *args, **kwargs)
    except Exception as e:
        error_str = str(e)
        # Handle weights_only issues
        if 'weights_only' in error_str or 'Unpickler' in error_str:
            kwargs['weights_only'] = False
            if not torch.cuda.is_available():
                kwargs['map_location'] = 'cpu'
            return _original_torch_load(f, *args, **kwargs)
        # Handle CUDA deserialization issues
        if 'CUDA' in error_str or 'cuda' in error_str:
            kwargs['map_location'] = 'cpu'
            return _original_torch_load(f, *args, **kwargs)
        raise

torch.load = _patched_torch_load

{$import}

device = {$deviceCode}
print(f"Using device: {device}", file=sys.stderr, flush=True)
{$seedCode}

print("Loading model...", file=sys.stderr, flush=True)
model = {$className}.from_pretrained(device=device)

print("Generating audio...", file=sys.stderr, flush=True)
text = "{$text}"
wav = model.generate(text, {$generateArgsStr})

# Save audio
output_path = "{$outputPath}"
ta.save(output_path, wav, model.sr)

# Calculate duration
duration = wav.shape[-1] / model.sr

# Output metadata as JSON
metadata = {
    "sample_rate": model.sr,
    "duration": float(duration),
    "output_path": output_path
}
print(json.dumps(metadata))
print("Audio saved to:", output_path, file=sys.stderr, flush=True)
PYTHON;
    }

    /**
     * Parse Python script output for metadata.
     *
     * @return array<string, mixed>
     */
    private function parseOutput(string $output): array
    {
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '{') && str_ends_with($line, '}')) {
                $data = json_decode($line, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        return [];
    }

    // =========================================================================
    // AUDIO CONVERSION
    // =========================================================================

    /**
     * Convert the generated audio to the specified format.
     * Format is automatically detected from the output file extension.
     *
     * @param string $outputPath Output file path with extension (.mp3, .m4a, .ogg, .opus, .flac)
     * @param array<string, mixed> $options Format-specific options (bitrate, quality)
     * @param bool $deleteOriginal Delete the original WAV file after conversion
     * @throws \InvalidArgumentException If format is not supported
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
            default => throw new \InvalidArgumentException(
                "Unsupported audio format: .{$extension}. " .
                "Supported formats: mp3, m4a, aac, ogg, opus, flac"
            ),
        };
    }

    /**
     * Convert the generated audio to MP3 format.
     *
     * @param string|null $outputPath Output MP3 file path (null = auto-generate)
     * @param int $bitrate Bitrate in kbps (default: 192)
     * @param bool $deleteOriginal Delete the original WAV file after conversion
     */
    public function convertToMp3(?string $outputPath = null, int $bitrate = 192, bool $deleteOriginal = false): GenerationResult
    {
        $result = $this->generate();

        if (!$result->isSuccessful()) {
            return $result;
        }

        $outputPath = $outputPath ?? str_replace('.wav', '.mp3', $result->outputPath);
        $converter = new Support\AudioConverter($this->timeout);

        if ($converter->toMp3($result->outputPath, $outputPath, $bitrate)) {
            if ($deleteOriginal && file_exists($result->outputPath)) {
                unlink($result->outputPath);
            }

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $result->text,
                sampleRate: $result->sampleRate,
                duration: $result->duration,
                metadata: array_merge($result->metadata, ['converted_to' => 'mp3', 'bitrate' => $bitrate]),
            );
        }

        return GenerationResult::failure('Failed to convert to MP3');
    }

    /**
     * Convert the generated audio to M4A/AAC format.
     *
     * @param string|null $outputPath Output M4A file path (null = auto-generate)
     * @param int $bitrate Bitrate in kbps (default: 128)
     * @param bool $deleteOriginal Delete the original WAV file after conversion
     */
    public function convertToM4a(?string $outputPath = null, int $bitrate = 128, bool $deleteOriginal = false): GenerationResult
    {
        $result = $this->generate();

        if (!$result->isSuccessful()) {
            return $result;
        }

        $outputPath = $outputPath ?? str_replace('.wav', '.m4a', $result->outputPath);
        $converter = new Support\AudioConverter($this->timeout);

        if ($converter->toM4a($result->outputPath, $outputPath, $bitrate)) {
            if ($deleteOriginal && file_exists($result->outputPath)) {
                unlink($result->outputPath);
            }

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $result->text,
                sampleRate: $result->sampleRate,
                duration: $result->duration,
                metadata: array_merge($result->metadata, ['converted_to' => 'm4a', 'bitrate' => $bitrate]),
            );
        }

        return GenerationResult::failure('Failed to convert to M4A');
    }

    /**
     * Convert the generated audio to OGG Vorbis format.
     *
     * @param string|null $outputPath Output OGG file path (null = auto-generate)
     * @param int $quality Quality level 0-10 (default: 5)
     * @param bool $deleteOriginal Delete the original WAV file after conversion
     */
    public function convertToOgg(?string $outputPath = null, int $quality = 5, bool $deleteOriginal = false): GenerationResult
    {
        $result = $this->generate();

        if (!$result->isSuccessful()) {
            return $result;
        }

        $outputPath = $outputPath ?? str_replace('.wav', '.ogg', $result->outputPath);
        $converter = new Support\AudioConverter($this->timeout);

        if ($converter->toOgg($result->outputPath, $outputPath, $quality)) {
            if ($deleteOriginal && file_exists($result->outputPath)) {
                unlink($result->outputPath);
            }

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $result->text,
                sampleRate: $result->sampleRate,
                duration: $result->duration,
                metadata: array_merge($result->metadata, ['converted_to' => 'ogg', 'quality' => $quality]),
            );
        }

        return GenerationResult::failure('Failed to convert to OGG');
    }

    /**
     * Convert the generated audio to Opus format.
     *
     * @param string|null $outputPath Output Opus file path (null = auto-generate)
     * @param int $bitrate Bitrate in kbps (default: 96)
     * @param bool $deleteOriginal Delete the original WAV file after conversion
     */
    public function convertToOpus(?string $outputPath = null, int $bitrate = 96, bool $deleteOriginal = false): GenerationResult
    {
        $result = $this->generate();

        if (!$result->isSuccessful()) {
            return $result;
        }

        $outputPath = $outputPath ?? str_replace('.wav', '.opus', $result->outputPath);
        $converter = new Support\AudioConverter($this->timeout);

        if ($converter->toOpus($result->outputPath, $outputPath, $bitrate)) {
            if ($deleteOriginal && file_exists($result->outputPath)) {
                unlink($result->outputPath);
            }

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $result->text,
                sampleRate: $result->sampleRate,
                duration: $result->duration,
                metadata: array_merge($result->metadata, ['converted_to' => 'opus', 'bitrate' => $bitrate]),
            );
        }

        return GenerationResult::failure('Failed to convert to Opus');
    }

    /**
     * Convert the generated audio to FLAC format (lossless).
     *
     * @param string|null $outputPath Output FLAC file path (null = auto-generate)
     * @param bool $deleteOriginal Delete the original WAV file after conversion
     */
    public function convertToFlac(?string $outputPath = null, bool $deleteOriginal = false): GenerationResult
    {
        $result = $this->generate();

        if (!$result->isSuccessful()) {
            return $result;
        }

        $outputPath = $outputPath ?? str_replace('.wav', '.flac', $result->outputPath);
        $converter = new Support\AudioConverter($this->timeout);

        if ($converter->toFlac($result->outputPath, $outputPath)) {
            if ($deleteOriginal && file_exists($result->outputPath)) {
                unlink($result->outputPath);
            }

            return GenerationResult::success(
                outputPath: $outputPath,
                text: $result->text,
                sampleRate: $result->sampleRate,
                duration: $result->duration,
                metadata: array_merge($result->metadata, ['converted_to' => 'flac']),
            );
        }

        return GenerationResult::failure('Failed to convert to FLAC');
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Check system requirements.
     *
     * @return array<string, mixed>
     */
    public static function checkRequirements(): array
    {
        return (new RequirementsChecker())->check();
    }

    /**
     * Install Chatterbox TTS package.
     *
     * @return array{success: bool, error?: string, output?: string}
     */
    public static function install(?callable $onOutput = null): array
    {
        return (new RequirementsChecker())->installChatterbox($onOutput);
    }

    /**
     * List available models and their status.
     *
     * @return array<string, array{model: Model, available: bool, description: string}>
     */
    public static function listModels(): array
    {
        return (new ModelManager())->listModels();
    }

    /**
     * Convert an existing audio file to another format.
     *
     * @param string $inputPath Source audio file
     * @param string $outputPath Destination file path
     * @param string $format Target format (mp3, m4a, ogg, opus, flac)
     * @param array<string, mixed> $options Format-specific options
     */
    public static function convertAudio(string $inputPath, string $outputPath, string $format, array $options = []): bool
    {
        $converter = new Support\AudioConverter();

        return match (strtolower($format)) {
            'mp3' => $converter->toMp3($inputPath, $outputPath, $options['bitrate'] ?? 192),
            'm4a', 'aac' => $converter->toM4a($inputPath, $outputPath, $options['bitrate'] ?? 128),
            'ogg' => $converter->toOgg($inputPath, $outputPath, $options['quality'] ?? 5),
            'opus' => $converter->toOpus($inputPath, $outputPath, $options['bitrate'] ?? 96),
            'flac' => $converter->toFlac($inputPath, $outputPath),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Get audio file information.
     *
     * @return array{duration: float, sample_rate: int, channels: int, codec: string, bitrate: int}|null
     */
    public static function getAudioInfo(string $filePath): ?array
    {
        return (new Support\AudioConverter())->getInfo($filePath);
    }
}
