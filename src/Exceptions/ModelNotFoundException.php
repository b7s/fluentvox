<?php

declare(strict_types=1);

namespace B7s\FluentVox\Exceptions;

use B7s\FluentVox\Enums\Model;

/**
 * Thrown when a model is not available locally.
 */
class ModelNotFoundException extends FluentVoxException
{
    public static function notDownloaded(Model $model): self
    {
        return new self(
            "Model '{$model->value}' is not available locally. " .
            'It will be downloaded automatically on first use.'
        );
    }

    public static function downloadFailed(Model $model, string $reason): self
    {
        return new self(
            "Failed to download model '{$model->value}': {$reason}"
        );
    }
}
