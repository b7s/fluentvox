<?php

declare(strict_types=1);

namespace B7s\FluentVox\Enums;

/**
 * Available Chatterbox TTS models.
 */
enum Model: string
{
    /** Standard English model with CFG & exaggeration tuning (500M params) */
    case Chatterbox = 'chatterbox';

    /** Fast, efficient model with paralinguistic tags support (350M params) */
    case ChatterboxTurbo = 'chatterbox-turbo';

    /** Multilingual model supporting 23+ languages (500M params) */
    case ChatterboxMultilingual = 'chatterbox-multilingual';

    /**
     * Get the Python class name for this model.
     */
    public function pythonClass(): string
    {
        return match ($this) {
            self::Chatterbox => 'ChatterboxTTS',
            self::ChatterboxTurbo => 'ChatterboxTurbo',
            self::ChatterboxMultilingual => 'ChatterboxMultilingualTTS',
        };
    }

    /**
     * Get the Python import path for this model.
     */
    public function pythonImport(): string
    {
        return match ($this) {
            self::Chatterbox => 'from chatterbox.tts import ChatterboxTTS',
            self::ChatterboxTurbo => 'from chatterbox.turbo import ChatterboxTurbo',
            self::ChatterboxMultilingual => 'from chatterbox.mtl_tts import ChatterboxMultilingualTTS',
        };
    }

    /**
     * Check if this model supports multilingual synthesis.
     */
    public function isMultilingual(): bool
    {
        return $this === self::ChatterboxMultilingual;
    }

    /**
     * Check if this model supports paralinguistic tags.
     */
    public function supportsParalinguisticTags(): bool
    {
        return $this === self::ChatterboxTurbo;
    }

    /**
     * Get model description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Chatterbox => 'Standard English TTS with emotion control (500M params)',
            self::ChatterboxTurbo => 'Fast TTS with paralinguistic tags [laugh], [cough] (350M params)',
            self::ChatterboxMultilingual => 'Multilingual TTS supporting 23+ languages (500M params)',
        };
    }
}
