<?php

declare(strict_types=1);

namespace B7s\FluentVox\Console\Commands;

use B7s\FluentVox\Enums\Language;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\FluentVox;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generate speech from text via CLI.
 */
#[AsCommand(
    name: 'generate',
    description: 'Generate speech audio from text',
)]
class GenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('text', InputArgument::REQUIRED, 'Text to synthesize')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path')
            ->addOption('model', 'm', InputOption::VALUE_REQUIRED, 'Model to use', 'chatterbox')
            ->addOption('voice', null, InputOption::VALUE_REQUIRED, 'Reference audio for voice cloning')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Language code (for multilingual model)', 'en')
            ->addOption('exaggeration', null, InputOption::VALUE_REQUIRED, 'Expressiveness (0.25-2.0)', '0.5')
            ->addOption('temperature', null, InputOption::VALUE_REQUIRED, 'Randomness (0.05-5.0)', '0.8')
            ->addOption('cfg', null, InputOption::VALUE_REQUIRED, 'Pace/CFG weight (0.2-1.0)', '0.5')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Random seed (0 for random)', '0')
            ->setHelp(<<<'HELP'
The <info>generate</info> command synthesizes speech from text.

<info>Basic usage:</info>
  vendor/bin/fluentvox generate "Hello, world!"

<info>Save to specific file:</info>
  vendor/bin/fluentvox generate "Hello, world!" -o output.wav

<info>Use voice cloning:</info>
  vendor/bin/fluentvox generate "Hello, world!" --voice=reference.wav

<info>Use multilingual model:</info>
  vendor/bin/fluentvox generate "Bonjour le monde!" -m chatterbox-multilingual -l fr

<info>Adjust expression:</info>
  vendor/bin/fluentvox generate "Wow, amazing!" --exaggeration=0.8 --cfg=0.4

<info>Reproducible output:</info>
  vendor/bin/fluentvox generate "Hello!" --seed=42
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $text = $input->getArgument('text');
        $modelName = $input->getOption('model');

        // Validate model
        $model = Model::tryFrom($modelName);
        if ($model === null) {
            $io->error("Unknown model: {$modelName}");
            return Command::FAILURE;
        }

        // Build FluentVox instance
        $fluentVox = FluentVox::make()
            ->text($text)
            ->model($model)
            ->exaggeration((float) $input->getOption('exaggeration'))
            ->temperature((float) $input->getOption('temperature'))
            ->cfgWeight((float) $input->getOption('cfg'))
            ->seed((int) $input->getOption('seed'));

        // Set output path
        if ($outputPath = $input->getOption('output')) {
            $fluentVox->saveTo($outputPath);
        }

        // Set voice reference
        if ($voicePath = $input->getOption('voice')) {
            if (!file_exists($voicePath)) {
                $io->error("Voice reference file not found: {$voicePath}");
                return Command::FAILURE;
            }
            $fluentVox->voiceFrom($voicePath);
        }

        // Set language for multilingual model
        if ($model->isMultilingual()) {
            $langCode = $input->getOption('language');
            $language = Language::tryFrom($langCode);
            if ($language === null) {
                $io->error("Unknown language code: {$langCode}");
                $io->text('Available: ' . implode(', ', array_column(Language::cases(), 'value')));
                return Command::FAILURE;
            }
            $fluentVox->language($language);
        }

        // Show progress
        $io->text('Generating speech...');

        if ($output->isVerbose()) {
            $fluentVox->onProgress(function (string $data, bool $isError) use ($output) {
                $output->write($data);
            });
        }

        try {
            $result = $fluentVox->generate();

            if (!$result->isSuccessful()) {
                $io->error('Generation failed: ' . $result->error);
                return Command::FAILURE;
            }

            $io->success([
                'Audio generated successfully!',
                '',
                "Output: {$result->outputPath}",
                "Duration: {$result->getFormattedDuration()}",
                "Sample rate: {$result->sampleRate} Hz",
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
