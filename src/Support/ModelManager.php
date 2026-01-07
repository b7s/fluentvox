<?php

declare(strict_types=1);

namespace B7s\FluentVox\Support;

use B7s\FluentVox\Config;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Exceptions\ModelNotFoundException;

/**
 * Manages Chatterbox model downloads and availability.
 */
final class ModelManager
{
    private PythonRunner $python;

    public function __construct(?PythonRunner $python = null)
    {
        $this->python = $python ?? new PythonRunner(timeout: 1800); // 30 min for downloads
    }

    /**
     * Check if a model is available locally.
     */
    public function isModelAvailable(Model $model): bool
    {
        $script = $this->buildCheckScript($model);

        try {
            $output = trim($this->python->execute($script));
            return $output === 'AVAILABLE';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Download a model if not available.
     */
    public function ensureModel(Model $model, ?callable $onProgress = null): bool
    {
        if ($this->isModelAvailable($model)) {
            return true;
        }

        return $this->downloadModel($model, $onProgress);
    }

    /**
     * Download a specific model.
     */
    public function downloadModel(Model $model, ?callable $onProgress = null): bool
    {
        $script = $this->buildDownloadScript($model);

        try {
            $this->python->execute($script, $onProgress);
            return true;
        } catch (\Throwable $e) {
            throw ModelNotFoundException::downloadFailed($model, $e->getMessage());
        }
    }

    /**
     * Get the models cache directory.
     */
    public function getModelsPath(): string
    {
        $configPath = Config::get('models_path');

        if ($configPath !== null) {
            return $configPath;
        }

        // Default HuggingFace cache location
        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? '/tmp';
        return "{$home}/.cache/huggingface/hub";
    }

    /**
     * List all available models with their status.
     *
     * @return array<string, array{model: Model, available: bool, description: string}>
     */
    public function listModels(): array
    {
        $models = [];

        foreach (Model::cases() as $model) {
            $models[$model->value] = [
                'model' => $model,
                'available' => $this->isModelAvailable($model),
                'description' => $model->description(),
            ];
        }

        return $models;
    }

    /**
     * Build Python script to check model availability.
     */
    private function buildCheckScript(Model $model): string
    {
        $import = $model->pythonImport();
        $className = $model->pythonClass();

        return <<<PYTHON
import os
import sys

# Suppress warnings
os.environ['HF_HUB_DISABLE_PROGRESS_BARS'] = '1'
os.environ['TRANSFORMERS_VERBOSITY'] = 'error'

try:
    from huggingface_hub import try_to_load_from_cache
    from huggingface_hub.constants import HUGGINGFACE_HUB_CACHE
    
    # Check if model files exist in cache
    {$import}
    
    # Try to load - this will check cache
    model = {$className}.from_pretrained(device="cpu")
    print("AVAILABLE")
except ImportError:
    # Missing dependencies - model not available
    print("NOT_AVAILABLE")
except Exception:
    # Any other error - model not available
    print("NOT_AVAILABLE")
PYTHON;
    }

    /**
     * Build Python script to download model.
     */
    private function buildDownloadScript(Model $model): string
    {
        $import = $model->pythonImport();
        $className = $model->pythonClass();

        return <<<PYTHON
import sys
import traceback
import torch

print("Downloading {$model->value} model...", flush=True)

try:
    {$import}
    
    # Force download by loading the model
    device = "cuda" if torch.cuda.is_available() else "cpu"
    print(f"Using device: {device}", flush=True)
    
    print("Loading model from HuggingFace...", flush=True)
    model = {$className}.from_pretrained(device=device)
    print("Model downloaded successfully!", flush=True)
except ImportError as e:
    print(f"ERROR: Missing import - {e}", file=sys.stderr, flush=True)
    print(f"ERROR: Please ensure Chatterbox TTS is installed: pip install chatterbox-tts", file=sys.stderr, flush=True)
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
except Exception as e:
    print(f"ERROR: Failed to download model - {type(e).__name__}: {e}", file=sys.stderr, flush=True)
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
PYTHON;
    }
}
