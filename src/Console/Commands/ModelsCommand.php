<?php

declare(strict_types=1);

namespace B7s\FluentVox\Console\Commands;

use B7s\FluentVox\Console\Commands\Concerns\WithLoadingIndicator;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Support\ModelManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manage Chatterbox TTS models.
 */
#[AsCommand(
    name: 'models',
    description: 'List and download Chatterbox TTS models',
)]
class ModelsCommand extends Command
{
    use WithLoadingIndicator;
    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action to perform: list, download', 'list')
            ->addOption('model', 'm', InputOption::VALUE_REQUIRED, 'Model to download (chatterbox, chatterbox-turbo, chatterbox-multilingual)')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Download all models')
            ->setHelp(<<<'HELP'
The <info>models</info> command manages Chatterbox TTS models.

<info>List available models:</info>
  vendor/bin/fluentvox models
  vendor/bin/fluentvox models list

<info>Download a specific model:</info>
  vendor/bin/fluentvox models download --model=chatterbox
  vendor/bin/fluentvox models download -m chatterbox-turbo

<info>Download all models:</info>
  vendor/bin/fluentvox models download --all

<info>Available models:</info>
  - chatterbox: Standard English TTS (500M params)
  - chatterbox-turbo: Fast TTS with paralinguistic tags (350M params)
  - chatterbox-multilingual: 23+ languages support (500M params)
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        return match ($action) {
            'list' => $this->listModels($io, $output),
            'download' => $this->downloadModels($input, $io, $output),
            default => $this->invalidAction($io, $action),
        };
    }

    private function listModels(SymfonyStyle $io, OutputInterface $output): int
    {
        $io->title('Available Models');

        $modelManager = new ModelManager();
        
        $models = $this->withLoading(
            fn() => $modelManager->listModels(),
            'Checking model availability',
            $output,
            $io
        );

        $rows = [];
        foreach ($models as $name => $info) {
            $status = $info['available']
                ? '<fg=green>✓ Downloaded</>'
                : '<fg=yellow>○ Not downloaded</>';

            $rows[] = [$name, $status, $info['description']];
        }

        $io->table(['Model', 'Status', 'Description'], $rows);

        $io->text([
            '',
            'To download a model:',
            '  <info>vendor/bin/fluentvox models download --model=chatterbox</info>',
            '',
            'Models are also downloaded automatically on first use.',
        ]);

        return Command::SUCCESS;
    }

    private function downloadModels(InputInterface $input, SymfonyStyle $io, OutputInterface $output): int
    {
        $modelManager = new ModelManager();
        $modelsToDownload = [];

        if ($input->getOption('all')) {
            $modelsToDownload = Model::cases();
        } elseif ($modelName = $input->getOption('model')) {
            $model = Model::tryFrom($modelName);
            if ($model === null) {
                $io->error("Unknown model: {$modelName}");
                $io->text('Available models: chatterbox, chatterbox-turbo, chatterbox-multilingual');
                return Command::FAILURE;
            }
            $modelsToDownload = [$model];
        } else {
            $io->error('Please specify a model with --model or use --all to download all models.');
            return Command::FAILURE;
        }

        $io->title('Downloading Models');

        foreach ($modelsToDownload as $model) {
            $io->section("Model: {$model->value}");

            if ($modelManager->isModelAvailable($model)) {
                $io->success('Already downloaded');
                continue;
            }

            $io->text('Downloading (this may take a while)...');

            try {
                $success = $modelManager->downloadModel($model, function (string $data, bool $isError) use ($output) {
                    if ($output->isVerbose()) {
                        $output->write($data);
                    } else {
                        // Show progress dots
                        $output->write('.');
                    }
                });

                $io->newLine();

                if ($success) {
                    $io->success('Downloaded successfully');
                } else {
                    $io->error('Download failed');
                    return Command::FAILURE;
                }
            } catch (\Throwable $e) {
                $io->newLine();
                $io->error('Download failed: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->newLine();
        $io->success('All models downloaded successfully!');

        return Command::SUCCESS;
    }

    private function invalidAction(SymfonyStyle $io, string $action): int
    {
        $io->error("Unknown action: {$action}");
        $io->text('Available actions: list, download');
        return Command::FAILURE;
    }
}
