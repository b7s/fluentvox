<?php

declare(strict_types=1);

namespace B7s\FluentVox\Enums;

/**
 * Available compute devices for inference.
 */
enum Device: string
{
    /** Automatically detect best available device */
    case Auto = 'auto';

    /** Use NVIDIA CUDA GPU */
    case Cuda = 'cuda';

    /** Use Apple Metal Performance Shaders (macOS) */
    case Mps = 'mps';

    /** Use CPU only */
    case Cpu = 'cpu';

    /**
     * Get the Python device string.
     */
    public function toPython(): string
    {
        return match ($this) {
            self::Auto => $this->getAutoDetectCode(),
            self::Cuda => '"cuda"',
            self::Mps => '"mps"',
            self::Cpu => '"cpu"',
        };
    }

    /**
     * Get Python code for auto-detecting the best device.
     */
    private function getAutoDetectCode(): string
    {
        return <<<'PYTHON'
("cuda" if torch.cuda.is_available() else ("mps" if hasattr(torch.backends, 'mps') and torch.backends.mps.is_available() else "cpu"))
PYTHON;
    }

    /**
     * Get a human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Auto => 'Auto-detect best available device',
            self::Cuda => 'NVIDIA CUDA GPU',
            self::Mps => 'Apple Metal Performance Shaders',
            self::Cpu => 'CPU only',
        };
    }
}
