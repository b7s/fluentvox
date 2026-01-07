<?php

declare(strict_types=1);

namespace B7s\FluentVox\Support;

use B7s\FluentVox\Config;
use B7s\FluentVox\Enums\OperatingSystem;
use B7s\FluentVox\Exceptions\PythonNotFoundException;
use Symfony\Component\Process\Process;

/**
 * Handles Python process execution with cross-platform support.
 */
final class PythonRunner
{
    private ?string $pythonPath = null;
    private ?string $pythonVersion = null;

    public function __construct(
        private readonly int $timeout = 300,
        private readonly bool $verbose = false,
    ) {}

    /**
     * Execute a Python script and return the output.
     */
    public function execute(string $script, ?callable $onOutput = null): string
    {
        $pythonPath = $this->getPythonPath();
        $env = $this->getEnvironment();

        // Handle Windows 'py -3' style commands
        $command = $this->buildCommand($pythonPath, ['-c', $script]);

        $process = new Process(
            $command,
            env: $env,
            timeout: $this->timeout,
        );

        if ($onOutput !== null) {
            $process->start();

            foreach ($process as $type => $data) {
                $onOutput($data, $type === Process::ERR);
            }

            $process->wait();
        } else {
            $process->run();
        }

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            throw new \RuntimeException("Python execution failed: {$error}");
        }

        return $process->getOutput();
    }

    /**
     * Execute a Python script file.
     *
     * @param array<int, string> $args
     */
    public function executeFile(string $scriptPath, array $args = [], ?callable $onOutput = null): string
    {
        $pythonPath = $this->getPythonPath();
        $env = $this->getEnvironment();

        $command = $this->buildCommand($pythonPath, array_merge([$scriptPath], $args));
        $process = new Process($command, env: $env, timeout: $this->timeout);

        if ($onOutput !== null) {
            $process->start();

            foreach ($process as $type => $data) {
                $onOutput($data, $type === Process::ERR);
            }

            $process->wait();
        } else {
            $process->run();
        }

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            throw new \RuntimeException("Python execution failed: {$error}");
        }

        return $process->getOutput();
    }

    /**
     * Execute pip command.
     *
     * @param array<int, string> $args
     */
    public function pip(array $args, ?callable $onOutput = null): string
    {
        $pythonPath = $this->getPythonPath();
        $env = $this->getEnvironment();

        $command = $this->buildCommand($pythonPath, array_merge(['-m', 'pip'], $args));
        $process = new Process($command, env: $env, timeout: $this->timeout);

        if ($onOutput !== null) {
            $process->start();

            foreach ($process as $type => $data) {
                $onOutput($data, $type === Process::ERR);
            }

            $process->wait();
        } else {
            $process->run();
        }

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            throw new \RuntimeException("pip execution failed: {$error}");
        }

        return $process->getOutput();
    }

    /**
     * Get the Python executable path.
     */
    public function getPythonPath(): string
    {
        if ($this->pythonPath !== null) {
            return $this->pythonPath;
        }

        // Check config first
        $configPath = Config::get('python_path');
        if ($configPath !== null && $this->isValidPython($configPath)) {
            $this->pythonPath = $configPath;
            return $this->pythonPath;
        }

        // First, try to find Python in virtual environments (prioritize these)
        $venvCandidates = $this->getVirtualEnvironmentCandidates();
        foreach ($venvCandidates as $candidate) {
            if ($this->isValidPython($candidate)) {
                $this->pythonPath = $candidate;
                return $this->pythonPath;
            }
        }

        // Then, get OS-specific candidates
        $candidates = Platform::os()->getPythonCandidates();

        foreach ($candidates as $candidate) {
            if ($this->isValidPython($candidate)) {
                $this->pythonPath = $candidate;
                return $this->pythonPath;
            }
        }

        throw PythonNotFoundException::notInstalled();
    }

    /**
     * Get the Python version.
     */
    public function getPythonVersion(): string
    {
        if ($this->pythonVersion !== null) {
            return $this->pythonVersion;
        }

        $pythonPath = $this->getPythonPath();
        $command = $this->buildCommand($pythonPath, ['--version']);
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw PythonNotFoundException::notInstalled();
        }

        $output = trim($process->getOutput() ?: $process->getErrorOutput());
        preg_match('/Python (\d+\.\d+\.\d+)/', $output, $matches);

        $this->pythonVersion = $matches[1] ?? 'unknown';
        return $this->pythonVersion;
    }

    /**
     * Check if Python version meets minimum requirements.
     */
    public function validatePythonVersion(string $minVersion = '3.10.0'): bool
    {
        $version = $this->getPythonVersion();

        if (version_compare($version, $minVersion, '<')) {
            throw PythonNotFoundException::versionTooLow($version);
        }

        return true;
    }

    /**
     * Get the pip version.
     */
    public function getPipVersion(): ?string
    {
        try {
            $output = $this->pip(['--version']);
            preg_match('/pip (\d+\.\d+(?:\.\d+)?)/', $output, $matches);
            return $matches[1] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if a package is installed.
     */
    public function isPackageInstalled(string $package): bool
    {
        try {
            $this->pip(['show', $package]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get installed package version.
     */
    public function getPackageVersion(string $package): ?string
    {
        try {
            $output = $this->pip(['show', $package]);
            preg_match('/Version:\s*(\S+)/', $output, $matches);
            return $matches[1] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Build command array, handling space-separated commands like 'py -3'.
     *
     * @param array<int, string> $args
     * @return array<int, string>
     */
    private function buildCommand(string $executable, array $args): array
    {
        // Handle commands like 'py -3' on Windows
        if (str_contains($executable, ' ')) {
            $parts = explode(' ', $executable);
            return array_merge($parts, $args);
        }

        return array_merge([$executable], $args);
    }

    /**
     * Get environment variables for Python processes.
     *
     * @return array<string, string>
     */
    private function getEnvironment(): array
    {
        $env = Platform::getPythonEnv();

        // Inherit current environment
        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && !isset($env[$key])) {
                $env[$key] = $value;
            }
        }

        return $env;
    }

    /**
     * Check if a Python path is valid and executable.
     */
    private function isValidPython(string $path): bool
    {
        $command = $this->buildCommand($path, ['--version']);
        $process = new Process($command);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Get Python executable candidates from virtual environments.
     *
     * @return array<int, string>
     */
    private function getVirtualEnvironmentCandidates(): array
    {
        $candidates = [];
        $os = Platform::os();
        $separator = $os->getPathSeparator();
        $isWindows = $os === OperatingSystem::Windows;

        // Common virtual environment directory names
        $venvNames = ['.venv', 'venv', 'venv311', 'venv310', 'venv312', 'venv313', 'venv314', 'env', 'ENV'];

        // Directories to search for virtual environments
        $searchDirs = [
            Platform::homeDirectory(), // Home directory
        ];

        // Add current directory and parent directories up to 3 levels
        $currentDir = getcwd();
        for ($i = 0; $i < 3; $i++) {
            if ($currentDir && $currentDir !== '/') {
                $searchDirs[] = $currentDir;
                $currentDir = dirname($currentDir);
            }
        }

        // Remove duplicates
        $searchDirs = array_unique($searchDirs);

        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach ($venvNames as $venvName) {
                $venvPath = $dir . $separator . $venvName;

                if (!is_dir($venvPath)) {
                    continue;
                }

                // Check for Python executable in virtual environment
                if ($isWindows) {
                    $pythonExe = $venvPath . $separator . 'Scripts' . $separator . 'python.exe';
                    if (file_exists($pythonExe)) {
                        $candidates[] = $pythonExe;
                    }
                } else {
                    $pythonExe = $venvPath . $separator . 'bin' . $separator . 'python';
                    if (file_exists($pythonExe)) {
                        $candidates[] = $pythonExe;
                    }
                    // Also check for python3 symlink
                    $python3Exe = $venvPath . $separator . 'bin' . $separator . 'python3';
                    if (file_exists($python3Exe) && $python3Exe !== $pythonExe) {
                        $candidates[] = $python3Exe;
                    }
                }
            }
        }

        return $candidates;
    }
}
