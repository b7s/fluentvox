<?php

declare(strict_types=1);

use B7s\FluentVox\Enums\Device;
use B7s\FluentVox\Enums\Language;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Enums\OperatingSystem;

describe('Device Enum', function () {
    test('has all expected cases', function () {
        expect(Device::cases())->toHaveCount(4)
            ->and(Device::Auto)->toBeInstanceOf(Device::class)
            ->and(Device::Cuda)->toBeInstanceOf(Device::class)
            ->and(Device::Mps)->toBeInstanceOf(Device::class)
            ->and(Device::Cpu)->toBeInstanceOf(Device::class);
    });

    test('converts to python code', function () {
        expect(Device::Cuda->toPython())->toBe('"cuda"')
            ->and(Device::Mps->toPython())->toBe('"mps"')
            ->and(Device::Cpu->toPython())->toBe('"cpu"')
            ->and(Device::Auto->toPython())->toContain('cuda');
    });

    test('has descriptions', function () {
        expect(Device::Cuda->description())->toContain('NVIDIA')
            ->and(Device::Mps->description())->toContain('Apple')
            ->and(Device::Cpu->description())->toContain('CPU');
    });
});

describe('Language Enum', function () {
    test('has all expected languages', function () {
        $languages = Language::cases();

        expect($languages)->toHaveCount(23)
            ->and(Language::English)->toBeInstanceOf(Language::class)
            ->and(Language::French)->toBeInstanceOf(Language::class)
            ->and(Language::Spanish)->toBeInstanceOf(Language::class);
    });

    test('has language names', function () {
        expect(Language::English->name())->toBe('English')
            ->and(Language::French->name())->toBe('French')
            ->and(Language::Spanish->name())->toBe('Spanish')
            ->and(Language::Portuguese->name())->toBe('Portuguese');
    });

    test('has correct language codes', function () {
        expect(Language::English->value)->toBe('en')
            ->and(Language::French->value)->toBe('fr')
            ->and(Language::Spanish->value)->toBe('es')
            ->and(Language::Portuguese->value)->toBe('pt');
    });
});

describe('Model Enum', function () {
    test('has all expected models', function () {
        expect(Model::cases())->toHaveCount(3)
            ->and(Model::Chatterbox)->toBeInstanceOf(Model::class)
            ->and(Model::ChatterboxTurbo)->toBeInstanceOf(Model::class)
            ->and(Model::ChatterboxMultilingual)->toBeInstanceOf(Model::class);
    });

    test('has python class names', function () {
        expect(Model::Chatterbox->pythonClass())->toBe('ChatterboxTTS')
            ->and(Model::ChatterboxTurbo->pythonClass())->toBe('ChatterboxTurbo')
            ->and(Model::ChatterboxMultilingual->pythonClass())->toBe('ChatterboxMultilingualTTS');
    });

    test('has python imports', function () {
        expect(Model::Chatterbox->pythonImport())->toContain('from chatterbox.tts')
            ->and(Model::ChatterboxTurbo->pythonImport())->toContain('from chatterbox.turbo')
            ->and(Model::ChatterboxMultilingual->pythonImport())->toContain('from chatterbox.mtl_tts');
    });

    test('checks multilingual support', function () {
        expect(Model::Chatterbox->isMultilingual())->toBeFalse()
            ->and(Model::ChatterboxTurbo->isMultilingual())->toBeFalse()
            ->and(Model::ChatterboxMultilingual->isMultilingual())->toBeTrue();
    });

    test('checks paralinguistic tags support', function () {
        expect(Model::Chatterbox->supportsParalinguisticTags())->toBeFalse()
            ->and(Model::ChatterboxTurbo->supportsParalinguisticTags())->toBeTrue()
            ->and(Model::ChatterboxMultilingual->supportsParalinguisticTags())->toBeFalse();
    });

    test('has descriptions', function () {
        expect(Model::Chatterbox->description())->toContain('English')
            ->and(Model::ChatterboxTurbo->description())->toContain('Fast')
            ->and(Model::ChatterboxMultilingual->description())->toContain('Multilingual');
    });
});

describe('OperatingSystem Enum', function () {
    test('has all expected cases', function () {
        expect(OperatingSystem::cases())->toHaveCount(3)
            ->and(OperatingSystem::Linux)->toBeInstanceOf(OperatingSystem::class)
            ->and(OperatingSystem::MacOS)->toBeInstanceOf(OperatingSystem::class)
            ->and(OperatingSystem::Windows)->toBeInstanceOf(OperatingSystem::class);
    });

    test('detects current os', function () {
        $os = OperatingSystem::detect();

        expect($os)->toBeInstanceOf(OperatingSystem::class)
            ->and($os->value)->toBeIn(['linux', 'darwin', 'windows']);
    });

    test('has display names', function () {
        expect(OperatingSystem::Linux->displayName())->toBe('Linux')
            ->and(OperatingSystem::MacOS->displayName())->toBe('macOS')
            ->and(OperatingSystem::Windows->displayName())->toBe('Windows');
    });

    test('has path separators', function () {
        expect(OperatingSystem::Linux->getPathSeparator())->toBe('/')
            ->and(OperatingSystem::MacOS->getPathSeparator())->toBe('/')
            ->and(OperatingSystem::Windows->getPathSeparator())->toBe('\\');
    });

    test('checks unix compatibility', function () {
        expect(OperatingSystem::Linux->isUnix())->toBeTrue()
            ->and(OperatingSystem::MacOS->isUnix())->toBeTrue()
            ->and(OperatingSystem::Windows->isUnix())->toBeFalse();
    });

    test('has python candidates', function () {
        $candidates = OperatingSystem::Linux->getPythonCandidates();

        expect($candidates)->toBeArray()
            ->and($candidates)->toContain('python3')
            ->and($candidates)->not->toBeEmpty();
    });
});
