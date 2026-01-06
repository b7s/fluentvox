<?php

declare(strict_types=1);

namespace B7s\FluentVox\Support;

use B7s\FluentVox\Exceptions\ChatterboxNotInstalledException;
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
     */
    public function installChatterbox(?callable $onOutput = null): bool
    {
        try {
            $this->python->pip(['install', 'chatterbox-tts'], $onOutput);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Upgrade Chatterbox TTS package.
     */
    public function upgradeChatterbox(?callable $onOutput = null): bool
    {
        try {
            $this->python->pip(['install', '--upgrade', 'chatterbox-tts'], $onOutput);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Install PyTorch with the correct backend for the current platform.
     */
    public function installPyTorch(?callable $onOutput = null): bool
    {
        $args = $this->getPyTorchInstallArgs();

        try {
            $this->python->pip($args, $onOutput);
            return true;
        } catch (\Throwable) {
            return false;
        }
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
