<?php

declare(strict_types=1);

namespace B7s\FluentVox\Enums;

/**
 * Supported languages for Chatterbox Multilingual model.
 */
enum Language: string
{
    case Arabic = 'ar';
    case Danish = 'da';
    case German = 'de';
    case Greek = 'el';
    case English = 'en';
    case Spanish = 'es';
    case Finnish = 'fi';
    case French = 'fr';
    case Hebrew = 'he';
    case Hindi = 'hi';
    case Italian = 'it';
    case Japanese = 'ja';
    case Korean = 'ko';
    case Malay = 'ms';
    case Dutch = 'nl';
    case Norwegian = 'no';
    case Polish = 'pl';
    case Portuguese = 'pt';
    case Russian = 'ru';
    case Swedish = 'sv';
    case Swahili = 'sw';
    case Turkish = 'tr';
    case Chinese = 'zh';

    /**
     * Get the language name.
     */
    public function name(): string
    {
        return match ($this) {
            self::Arabic => 'Arabic',
            self::Danish => 'Danish',
            self::German => 'German',
            self::Greek => 'Greek',
            self::English => 'English',
            self::Spanish => 'Spanish',
            self::Finnish => 'Finnish',
            self::French => 'French',
            self::Hebrew => 'Hebrew',
            self::Hindi => 'Hindi',
            self::Italian => 'Italian',
            self::Japanese => 'Japanese',
            self::Korean => 'Korean',
            self::Malay => 'Malay',
            self::Dutch => 'Dutch',
            self::Norwegian => 'Norwegian',
            self::Polish => 'Polish',
            self::Portuguese => 'Portuguese',
            self::Russian => 'Russian',
            self::Swedish => 'Swedish',
            self::Swahili => 'Swahili',
            self::Turkish => 'Turkish',
            self::Chinese => 'Chinese',
        };
    }
}
