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
     * Optimized to check cache files without loading the full model (much faster).
     */
    private function buildCheckScript(Model $model): string
    {
        // Map models to their HuggingFace repository IDs
        // Note: ChatterboxMultilingual uses the same repo as Chatterbox (ResembleAI/chatterbox)
        $repoId = match ($model) {
            Model::Chatterbox => 'ResembleAI/chatterbox',
            Model::ChatterboxTurbo => 'resemble-ai/chatterbox-turbo',
            Model::ChatterboxMultilingual => 'ResembleAI/chatterbox', // Same repo as Chatterbox
        };

        return <<<PYTHON
import os
import sys
import glob

# Suppress warnings
os.environ['HF_HUB_DISABLE_PROGRESS_BARS'] = '1'
os.environ['TRANSFORMERS_VERBOSITY'] = 'error'

def normalize_name(name):
    """Normalize name for comparison (remove hyphens/spaces, lowercase)."""
    import re
    return re.sub(r'[-\s]', '', name).lower()

def find_model_directory(cache_dir, repo_id):
    """Find model directory with case-insensitive and hyphen-insensitive search."""
    # Expected directory name format: models--{org}--{model}
    expected_dir = f"models--{repo_id.replace('/', '--')}"
    expected_path = os.path.join(cache_dir, expected_dir)
    
    # Try exact match first
    if os.path.exists(expected_path):
        return expected_path
    
    # Extract expected parts
    expected_parts = repo_id.split('/')
    expected_org_normalized = normalize_name(expected_parts[0])
    expected_model_normalized = normalize_name(expected_parts[1])
    
    # Search through all model directories
    pattern = os.path.join(cache_dir, "models--*")
    for dir_path in glob.glob(pattern):
        dir_name = os.path.basename(dir_path)
        
        # Extract parts from directory name: models--Org--Model
        if not dir_name.startswith('models--'):
            continue
            
        parts = dir_name.replace('models--', '').split('--')
        if len(parts) >= 2:
            org_part_normalized = normalize_name(parts[0])
            model_part_normalized = normalize_name(parts[1])
            
            # Compare normalized names (handles ResembleAI vs resemble-ai)
            if org_part_normalized == expected_org_normalized and model_part_normalized == expected_model_normalized:
                return dir_path
    
    return None

def check_directory_has_files(directory):
    """Check if directory contains any actual files (not just empty subdirs)."""
    try:
        for root, dirs, files in os.walk(directory):
            # Skip .locks directories
            if '.locks' in root:
                continue
            if files:
                return True
    except:
        pass
    return False

try:
    from huggingface_hub import try_to_load_from_cache
    from huggingface_hub.constants import HUGGINGFACE_HUB_CACHE
    
    # Model repository ID
    repo_id = "{$repoId}"
    
    # For multilingual model, check for specific multilingual files
    # since it shares the same repo as the regular chatterbox model
    model_files = [
        "config.json",
        "model.safetensors",
        "pytorch_model.bin",
        "model.safetensors.index.json",
        "model_index.json",
    ]
    
    # Multilingual-specific files to check
    multilingual_files = [
        "t3_mtl23ls_v2.safetensors",
        "grapheme_mtl_merged_expanded_v1.json",
    ]
    
    found_files = 0
    
    # For multilingual model, check for multilingual-specific files first
    # since it shares the same repo as the regular chatterbox model
    if "multilingual" in "{$model->value}":
        for filename in multilingual_files:
            cached_path = try_to_load_from_cache(repo_id=repo_id, filename=filename)
            if cached_path is not None and os.path.exists(cached_path):
                found_files += 1
                break
    
    # Also check for common model files
    if found_files == 0:
        for filename in model_files:
            cached_path = try_to_load_from_cache(repo_id=repo_id, filename=filename)
            if cached_path is not None and os.path.exists(cached_path):
                found_files += 1
                break  # Found at least one file, that's enough
    
    # If we found at least one key file, consider the model available
    if found_files > 0:
        print("AVAILABLE")
        sys.exit(0)
    
    # Fallback: check if the model directory exists (case-insensitive)
    cache_dir = HUGGINGFACE_HUB_CACHE
    model_dir = find_model_directory(cache_dir, repo_id)
    
    if model_dir and os.path.exists(model_dir):
        # Check if directory has any files (not just empty)
        if check_directory_has_files(model_dir):
            print("AVAILABLE")
            sys.exit(0)
    
    print("NOT_AVAILABLE")
except ImportError:
    # Missing dependencies - try fallback check using filesystem only
    try:
        import os
        repo_id = "{$repoId}"
        home = os.path.expanduser('~')
        cache_dir = os.path.join(home, '.cache', 'huggingface', 'hub')
        
        model_dir = find_model_directory(cache_dir, repo_id)
        if model_dir and os.path.exists(model_dir):
            # Check if directory has any files
            if check_directory_has_files(model_dir):
                print("AVAILABLE")
                sys.exit(0)
        
        print("NOT_AVAILABLE")
    except Exception as e:
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
import os

print("Downloading {$model->value} model...", flush=True)

# Check dependencies first
try:
    import torch
except ImportError:
    print("ERROR: PyTorch is not installed.", file=sys.stderr, flush=True)
    print("ERROR: Please install PyTorch first:", file=sys.stderr, flush=True)
    print("ERROR:   pip install torch torchaudio", file=sys.stderr, flush=True)
    print("ERROR: Or run: vendor/bin/fluentvox install --pytorch", file=sys.stderr, flush=True)
    sys.exit(1)

try:
    # Force CPU mode by hiding CUDA devices before importing
    # This helps prevent CUDA initialization issues
    os.environ['CUDA_VISIBLE_DEVICES'] = ''
    
    # Monkey-patch torch.load to always use CPU map_location
    # This fixes the issue where models saved with CUDA try to load on CPU
    # We need to intercept ALL calls to torch.load, including those from libraries
    original_torch_load = torch.load
    
    def cpu_torch_load(f, map_location=None, *args, **kwargs):
        # Always force map_location to CPU, overriding any None or CUDA values
        map_location = 'cpu'
        return original_torch_load(f, map_location=map_location, *args, **kwargs)
    
    torch.load = cpu_torch_load
    
    # Also patch torch.jit.load if available
    if hasattr(torch.jit, 'load'):
        original_jit_load = torch.jit.load
        def cpu_jit_load(f, map_location=None, *args, **kwargs):
            map_location = 'cpu'
            return original_jit_load(f, map_location=map_location, *args, **kwargs)
        torch.jit.load = cpu_jit_load
    
    # Patch torch.serialization.load (alias for torch.load)
    if hasattr(torch.serialization, 'load'):
        torch.serialization.load = cpu_torch_load
    
    # Patch torch._utils._rebuild_tensor_v2 which is used internally
    if hasattr(torch._utils, '_rebuild_tensor_v2'):
        original_rebuild = torch._utils._rebuild_tensor_v2
        def cpu_rebuild_tensor_v2(storage, storage_offset, size, stride, requires_grad, backward_hooks):
            # Force storage to CPU
            if hasattr(storage, 'device') and str(storage.device) != 'cpu':
                storage = storage.cpu()
            return original_rebuild(storage, storage_offset, size, stride, requires_grad, backward_hooks)
        torch._utils._rebuild_tensor_v2 = cpu_rebuild_tensor_v2
    
    # Patch default_restore_location to always use CPU
    if hasattr(torch.serialization, 'default_restore_location'):
        original_restore = torch.serialization.default_restore_location
        def cpu_restore_location(storage, location):
            return original_restore(storage, 'cpu')
        torch.serialization.default_restore_location = cpu_restore_location
    
    # Also patch any potential HuggingFace transformers loading
    # This needs to happen before importing Chatterbox
    try:
        import transformers
        if hasattr(transformers, 'modeling_utils'):
            # Patch the model loading in transformers if possible
            pass  # transformers handles this differently, but we'll catch it via torch.load
    except ImportError:
        pass
    
    {$import}
    
    # Always use CPU for downloading to avoid CUDA/device issues
    # The model can be moved to GPU later during actual inference if needed
    print("Using CPU for model download (works on all systems)...", flush=True)
    
    print("Loading model from HuggingFace...", flush=True)
    
    # Load model with explicit CPU device
    # The monkey-patch above ensures torch.load uses CPU map_location
    model = {$className}.from_pretrained(device="cpu")
    
    # Get the model cache path from HuggingFace
    try:
        from huggingface_hub import constants
        import os
        
        # Get the actual cache directory used by HuggingFace
        cache_dir = constants.HUGGINGFACE_HUB_CACHE
        if not cache_dir:
            # Fallback to default location
            home = os.path.expanduser('~')
            cache_dir = os.path.join(home, '.cache', 'huggingface', 'hub')
        
        # Model repository identifier
        # Note: multilingual uses same repo as regular chatterbox
        model_repo = match ("{$model->value}") {
            "chatterbox-multilingual" => "ResembleAI/chatterbox",
            default => "resemble-ai/{$model->value}",
        }
        
        print("Model downloaded successfully!", flush=True)
        print(f"Model cache directory: {cache_dir}", flush=True)
        print(f"Model repository: {model_repo}", flush=True)
        print(f"Full path: {cache_dir}/models--resemble-ai--{$model->value}", flush=True)
    except Exception:
        # If we can't determine the path, just confirm success
        import os
        home = os.path.expanduser('~')
        default_cache = os.path.join(home, '.cache', 'huggingface', 'hub')
        print("Model downloaded successfully!", flush=True)
        print(f"Model cache directory: {default_cache}", flush=True)
except ImportError as e:
    print(f"ERROR: Missing import - {e}", file=sys.stderr, flush=True)
    print(f"ERROR: Please ensure Chatterbox TTS is installed: pip install chatterbox-tts", file=sys.stderr, flush=True)
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
except RuntimeError as e:
    error_msg = str(e)
    if "CUDA" in error_msg or "cuda" in error_msg.lower() or "deserialize" in error_msg.lower() or "map_location" in error_msg.lower():
        print("=" * 70, file=sys.stderr, flush=True)
        print("ERROR: CUDA Device Mismatch", file=sys.stderr, flush=True)
        print("=" * 70, file=sys.stderr, flush=True)
        print(f"ERROR: {error_msg}", file=sys.stderr, flush=True)
        print("", file=sys.stderr, flush=True)
        print("ERROR: The model checkpoint was saved with CUDA weights, but your", file=sys.stderr, flush=True)
        print("ERROR: system doesn't have CUDA available. This is a limitation of", file=sys.stderr, flush=True)
        print("ERROR: how the model was saved by the Chatterbox library.", file=sys.stderr, flush=True)
        print("", file=sys.stderr, flush=True)
        print("ERROR: SOLUTIONS:", file=sys.stderr, flush=True)
        print("ERROR:", file=sys.stderr, flush=True)
        print("ERROR: Option 1: Install PyTorch with CUDA support", file=sys.stderr, flush=True)
        print("ERROR:   pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu118", file=sys.stderr, flush=True)
        print("ERROR:", file=sys.stderr, flush=True)
        print("ERROR: Option 2: Download on a machine with NVIDIA GPU", file=sys.stderr, flush=True)
        print("ERROR:   Then copy the model cache from ~/.cache/huggingface/hub", file=sys.stderr, flush=True)
        print("ERROR:", file=sys.stderr, flush=True)
        print("ERROR: Option 3: Use a different model (chatterbox or chatterbox-turbo)", file=sys.stderr, flush=True)
        print("ERROR:   These may not have the same CUDA dependency issue", file=sys.stderr, flush=True)
        print("=" * 70, file=sys.stderr, flush=True)
    else:
        print(f"ERROR: Runtime error - {type(e).__name__}: {error_msg}", file=sys.stderr, flush=True)
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
except Exception as e:
    print(f"ERROR: Failed to download model - {type(e).__name__}: {e}", file=sys.stderr, flush=True)
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
PYTHON;
    }
}
