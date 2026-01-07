<?php

declare(strict_types=1);

namespace B7s\FluentVox\Support;

use B7s\FluentVox\Exceptions\PythonNotFoundException;
use Symfony\Component\Process\Process;

/**
 * Validates system requirements for FluentVox with cross-platform support.
 */
final class RequirementsChecker
{
    private PythonRunner $python;

    public function __construct(?PythonRunner $python = null)
    {
        $this->python = $python ?? new PythonRunner();
    }

    /**
     * Get the Python executable path.
     */
    public function getPythonPath(): string
    {
        return $this->python->getPythonPath();
    }

    /**
     * Run all requirement checks.
     *
     * @return array{passed: bool, checks: array<string, array{status: bool, message: string}>}
     */
    public function check(): array
    {
        $checks = [];

        // Check platform
        $checks['platform'] = $this->checkPlatform();

        // Check Python
        $checks['python'] = $this->checkPython();

        // Check pip
        $checks['pip'] = $this->checkPip();

        // Check PyTorch
        $checks['pytorch'] = $this->checkPyTorch();

        // Check Chatterbox
        $checks['chatterbox'] = $this->checkChatterbox();

        // Check FFmpeg
        $checks['ffmpeg'] = $this->checkFfmpeg();

        // Check GPU acceleration (optional)
        $checks['gpu'] = $this->checkGpuAcceleration();

        $passed = array_reduce(
            $checks,
            fn(bool $carry, array $check) => $carry && ($check['status'] || (isset($check['optional']) && $check['optional'])),
            true
        );

        return [
            'passed' => $passed,
            'checks' => $checks,
        ];
    }

    /**
     * Check platform compatibility.
     *
     * @return array<string, mixed>
     */
    public function checkPlatform(): array
    {
        $info = Platform::info();

        return [
            'status' => true,
            'optional' => false,
            'message' => sprintf(
                '%s %s (%s)',
                $info['os_name'],
                $info['architecture'],
                $info['is_apple_silicon'] ? 'Apple Silicon' : 'Intel/AMD'
            ),
        ];
    }

    /**
     * Check if Python is installed and meets version requirements.
     *
     * @return array<string, mixed>
     */
    public function checkPython(): array
    {
        try {
            $version = $this->python->getPythonVersion();
            $this->python->validatePythonVersion('3.10.0');

            return [
                'status' => true,
                'optional' => false,
                'message' => "Python {$version} installed",
            ];
        } catch (PythonNotFoundException $e) {
            $installHint = $this->getPythonInstallHint();
            return [
                'status' => false,
                'optional' => false,
                'message' => $e->getMessage() . ' ' . $installHint,
            ];
        }
    }

    /**
     * Check if pip is available.
     *
     * @return array<string, mixed>
     */
    public function checkPip(): array
    {
        $version = $this->python->getPipVersion();

        if ($version !== null) {
            return [
                'status' => true,
                'optional' => false,
                'message' => "pip {$version} installed",
            ];
        }

        return [
            'status' => false,
            'optional' => false,
            'message' => 'pip is not installed. Run: python -m ensurepip --upgrade',
        ];
    }

    /**
     * Check if PyTorch is installed with correct backend.
     *
     * @return array<string, mixed>
     */
    public function checkPyTorch(): array
    {
        try {
            $script = $this->buildPyTorchCheckScript();
            $output = trim($this->python->execute($script));

            if (str_starts_with($output, 'NOT_INSTALLED')) {
                $installCmd = $this->getPyTorchInstallCommand();
                return [
                    'status' => false,
                    'optional' => false,
                    'message' => "PyTorch is not installed. Run: {$installCmd}",
                ];
            }

            return [
                'status' => true,
                'optional' => false,
                'message' => $output,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'optional' => false,
                'message' => 'PyTorch check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Chatterbox TTS is installed.
     *
     * @return array<string, mixed>
     */
    public function checkChatterbox(): array
    {
        try {
            $script = <<<'PYTHON'
try:
    import chatterbox
    version = getattr(chatterbox, '__version__', 'installed')
    print(f"OK:{version}")
except ImportError:
    print('NOT_INSTALLED')
except Exception as e:
    print(f'ERROR:{e}')
PYTHON;

            $output = trim($this->python->execute($script));

            if ($output === 'NOT_INSTALLED') {
                return [
                    'status' => false,
                    'optional' => false,
                    'message' => 'Chatterbox TTS is not installed. Run: pip install chatterbox-tts',
                ];
            }

            if (str_starts_with($output, 'ERROR:')) {
                return [
                    'status' => false,
                    'optional' => false,
                    'message' => 'Chatterbox TTS error: ' . substr($output, 6),
                ];
            }

            $version = str_starts_with($output, 'OK:') ? substr($output, 3) : $output;

            return [
                'status' => true,
                'optional' => false,
                'message' => "Chatterbox TTS {$version}",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'optional' => false,
                'message' => 'Chatterbox TTS check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if FFmpeg is installed.
     *
     * @return array<string, mixed>
     */
    public function checkFfmpeg(): array
    {
        $ffmpegPath = $this->findFfmpeg();

        if ($ffmpegPath !== null) {
            $version = $this->getFfmpegVersion($ffmpegPath);
            return [
                'status' => true,
                'optional' => false,
                'message' => "FFmpeg {$version} installed at {$ffmpegPath}",
            ];
        }

        $installHint = $this->getFfmpegInstallHint();
        return [
            'status' => false,
            'optional' => false,
            'message' => "FFmpeg is not installed. {$installHint}",
        ];
    }

    /**
     * Check GPU acceleration availability (CUDA for Linux/Windows, MPS for macOS).
     *
     * @return array<string, mixed>
     */
    public function checkGpuAcceleration(): array
    {
        try {
            $script = $this->buildGpuCheckScript();
            $output = trim($this->python->execute($script));

            if ($output === 'NO_GPU') {
                return [
                    'status' => false,
                    'optional' => true,
                    'message' => 'No GPU acceleration available (CPU mode will be used)',
                ];
            }

            return [
                'status' => true,
                'optional' => true,
                'message' => $output,
            ];
        } catch (\Throwable) {
            return [
                'status' => false,
                'optional' => true,
                'message' => 'GPU check failed (CPU mode will be used)',
            ];
        }
    }

    /**
     * Install Chatterbox TTS package.
     *
     * @return array{success: bool, error?: string, output?: string}
     */
    public function installChatterbox(?callable $onOutput = null): array
    {
        $outputBuffer = '';
        $errorBuffer = '';
        
        $captureOutput = function (string $data, bool $isError) use ($onOutput, &$outputBuffer, &$errorBuffer) {
            if ($isError) {
                $errorBuffer .= $data;
            } else {
                $outputBuffer .= $data;
            }
            
            if ($onOutput !== null) {
                $onOutput($data, $isError);
            }
        };

        try {
            $this->python->pip(['install', 'chatterbox-tts'], $captureOutput);
            return ['success' => true];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            if ($errorBuffer) {
                $errorMessage .= "\n\nError output:\n" . trim($errorBuffer);
            }
            if ($outputBuffer) {
                $errorMessage .= "\n\nStandard output:\n" . trim($outputBuffer);
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'output' => $outputBuffer,
            ];
        }
    }

    /**
     * Upgrade Chatterbox TTS package.
     *
     * @return array{success: bool, error?: string, output?: string}
     */
    public function upgradeChatterbox(?callable $onOutput = null): array
    {
        $outputBuffer = '';
        $errorBuffer = '';
        
        $captureOutput = function (string $data, bool $isError) use ($onOutput, &$outputBuffer, &$errorBuffer) {
            if ($isError) {
                $errorBuffer .= $data;
            } else {
                $outputBuffer .= $data;
            }
            
            if ($onOutput !== null) {
                $onOutput($data, $isError);
            }
        };

        try {
            $this->python->pip(['install', '--upgrade', 'chatterbox-tts'], $captureOutput);
            return ['success' => true];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            if ($errorBuffer) {
                $errorMessage .= "\n\nError output:\n" . trim($errorBuffer);
            }
            if ($outputBuffer) {
                $errorMessage .= "\n\nStandard output:\n" . trim($outputBuffer);
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'output' => $outputBuffer,
            ];
        }
    }

    /**
     * Install PyTorch with the correct backend for the current platform.
     *
     * @return array{success: bool, error?: string, output?: string}
     */
    public function installPyTorch(?callable $onOutput = null): array
    {
        $args = $this->getPyTorchInstallArgs();
        $outputBuffer = '';
        $errorBuffer = '';
        
        $captureOutput = function (string $data, bool $isError) use ($onOutput, &$outputBuffer, &$errorBuffer) {
            if ($isError) {
                $errorBuffer .= $data;
            } else {
                $outputBuffer .= $data;
            }
            
            if ($onOutput !== null) {
                $onOutput($data, $isError);
            }
        };

        try {
            $this->python->pip($args, $captureOutput);
            return ['success' => true];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            if ($errorBuffer) {
                $errorMessage .= "\n\nError output:\n" . trim($errorBuffer);
            }
            if ($outputBuffer) {
                $errorMessage .= "\n\nStandard output:\n" . trim($outputBuffer);
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'output' => $outputBuffer,
            ];
        }
    }

    /**
     * Find FFmpeg executable in system PATH or local installation.
     */
    private function findFfmpeg(): ?string
    {
        $candidates = Platform::isWindows()
            ? ['ffmpeg.exe', 'ffmpeg']
            : ['ffmpeg'];

        foreach ($candidates as $candidate) {
            $process = new Process([$candidate, '-version']);
            $process->run();

            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        // Check local installation in vendor/bin or bin directory
        $localPaths = [
            __DIR__ . '/../../bin/ffmpeg',
            __DIR__ . '/../../vendor/bin/ffmpeg',
        ];

        if (Platform::isWindows()) {
            $localPaths = array_map(fn($path) => $path . '.exe', $localPaths);
        }

        foreach ($localPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get FFmpeg version.
     */
    private function getFfmpegVersion(string $ffmpegPath): string
    {
        $process = new Process([$ffmpegPath, '-version']);
        $process->run();

        if (!$process->isSuccessful()) {
            return 'unknown';
        }

        $output = $process->getOutput();
        if (preg_match('/ffmpeg version ([^\s]+)/', $output, $matches)) {
            return $matches[1];
        }

        return 'unknown';
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
     * Get platform-specific Python installation hint.
     */
    private function getPythonInstallHint(): string
    {
        return match (Platform::os()) {
            \B7s\FluentVox\Enums\OperatingSystem::Windows => 'Download from https://python.org or run: winget install Python.Python.3.11',
            \B7s\FluentVox\Enums\OperatingSystem::MacOS => 'Run: brew install python@3.11',
            \B7s\FluentVox\Enums\OperatingSystem::Linux => 'Run: sudo apt install python3.11 python3.11-venv (Ubuntu/Debian) or sudo dnf install python3.11 (Fedora)',
        };
    }

    /**
     * Get platform-specific PyTorch install command.
     */
    private function getPyTorchInstallCommand(): string
    {
        $args = $this->getPyTorchInstallArgs();
        return 'pip ' . implode(' ', $args);
    }

    /**
     * Get platform-specific PyTorch install arguments.
     *
     * @return array<int, string>
     */
    private function getPyTorchInstallArgs(): array
    {
        // macOS uses MPS (Metal Performance Shaders)
        if (Platform::isMacOS()) {
            return ['install', 'torch', 'torchaudio'];
        }

        // Linux/Windows - try CUDA first, fallback to CPU
        if (Platform::hasNvidiaGpuPotential()) {
            // Install with CUDA support
            return [
                'install',
                'torch',
                'torchaudio',
                '--index-url',
                'https://download.pytorch.org/whl/cu121',
            ];
        }

        // CPU only
        return ['install', 'torch', 'torchaudio'];
    }

    /**
     * Build Python script to check PyTorch installation.
     */
    private function buildPyTorchCheckScript(): string
    {
        return <<<'PYTHON'
try:
    import torch
    version = torch.__version__
    
    backends = []
    if torch.cuda.is_available():
        backends.append(f"CUDA {torch.version.cuda}")
    if hasattr(torch.backends, 'mps') and torch.backends.mps.is_available():
        backends.append("MPS (Metal)")
    if not backends:
        backends.append("CPU only")
    
    print(f"PyTorch {version} ({', '.join(backends)})")
except ImportError:
    print("NOT_INSTALLED")
except Exception as e:
    print(f"NOT_INSTALLED: {e}")
PYTHON;
    }

    /**
     * Build Python script to check GPU acceleration.
     */
    private function buildGpuCheckScript(): string
    {
        return <<<'PYTHON'
import torch

# Check CUDA (NVIDIA)
if torch.cuda.is_available():
    device_name = torch.cuda.get_device_name(0)
    cuda_version = torch.version.cuda
    print(f"CUDA {cuda_version} ({device_name})")
# Check MPS (Apple Silicon)
elif hasattr(torch.backends, 'mps') and torch.backends.mps.is_available():
    print("MPS (Apple Metal Performance Shaders)")
else:
    print("NO_GPU")
PYTHON;
    }
}
