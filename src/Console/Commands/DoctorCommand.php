<?php

declare(strict_types=1);

namespace B7s\FluentVox\Console\Commands;

use B7s\FluentVox\Config;
use B7s\FluentVox\Enums\Model;
use B7s\FluentVox\Support\ModelManager;
use B7s\FluentVox\Support\Platform;
use B7s\FluentVox\Support\RequirementsChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Diagnose FluentVox installation and configuration.
 */
#[AsCommand(
    name: 'doctor',
    description: 'Check FluentVox installation and diagnose issues',
)]
class DoctorCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('download-default', null, InputOption::VALUE_NONE, 'Download the default model if not available')
            ->setHelp(<<<'HELP'
The <info>doctor</info> command checks your FluentVox installation and diagnoses any issues.

It verifies:
  - Platform compatibility (Linux, macOS, Windows)
  - Python installation and version
  - pip availability
  - PyTorch installation and GPU support
  - Chatterbox TTS installation
  - Model availability

<info>Usage:</info>
  vendor/bin/fluentvox doctor
  vendor/bin/fluentvox doctor --download-default
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('FluentVox Doctor');

        // Platform Information
        $io->section('Platform Information');
        $platformInfo = Platform::info();

        $io->table(
            ['Property', 'Value'],
            [
                ['Operating System', $platformInfo['os_name']],
                ['Architecture', $platformInfo['architecture']],
                ['PHP Version', $platformInfo['php_version']],
                ['Home Directory', $platformInfo['home_directory']],
                ['Cache Directory', $platformInfo['cache_directory']],
                ['Apple Silicon', $platformInfo['is_apple_silicon'] ? 'Yes' : 'No'],
                ['NVIDIA GPU Potential', $platformInfo['has_nvidia_potential'] ? 'Yes' : 'No'],
            ]
        );

        // Requirements Check
        $io->section('Requirements Check');
        $checker = new RequirementsChecker();
        $results = $checker->check();

        $rows = [];
        foreach ($results['checks'] as $name => $check) {
            $isOptional = isset($check['optional']) && $check['optional'];
            $status = $check['status']
                ? '<fg=green>✓ PASS</>'
                : ($isOptional ? '<fg=yellow>○ OPTIONAL</>' : '<fg=red>✗ FAIL</>');

            $rows[] = [ucfirst($name), $status, $check['message']];
        }

        $io->table(['Check', 'Status', 'Details'], $rows);

        // Model Status
        $io->section('Model Status');
        $modelManager = new ModelManager();
        $models = $modelManager->listModels();

        $modelRows = [];
        $defaultModelName = Config::get('default_model', 'chatterbox');
        $defaultModel = Model::tryFrom($defaultModelName);
        $defaultModelAvailable = false;

        foreach ($models as $name => $info) {
            $isDefault = $name === $defaultModelName;
            $status = $info['available']
                ? '<fg=green>✓ Available</>'
                : '<fg=yellow>○ Not downloaded</>';

            $description = $info['description'];
            if ($isDefault) {
                $description = '[DEFAULT] ' . $description;
                $defaultModelAvailable = $info['available'];
            }

            $modelRows[] = [$name, $status, $description];
        }

        $io->table(['Model', 'Status', 'Description'], $modelRows);

        // Default Model Recommendation
        if ($defaultModel !== null && !$defaultModelAvailable) {
            $io->section('Default Model');
            $io->warning([
                "Default model '{$defaultModelName}' is not downloaded.",
                '',
                'To download it, run:',
                "  <info>vendor/bin/fluentvox models download --model={$defaultModelName}</info>",
                '',
                'Or use the --download-default flag:',
                '  <info>vendor/bin/fluentvox doctor --download-default</info>',
            ]);

            // Download default model if requested
            if ($input->getOption('download-default')) {
                $io->section('Downloading Default Model');
                $io->text("Downloading {$defaultModelName}...");

                try {
                    $success = $modelManager->downloadModel($defaultModel, function (string $data, bool $isError) use ($output) {
                        if ($output->isVerbose()) {
                            $output->write($data);
                        } else {
                            $output->write('.');
                        }
                    });

                    $io->newLine();

                    if ($success) {
                        $io->success("Default model '{$defaultModelName}' downloaded successfully!");
                    } else {
                        $io->error("Failed to download default model '{$defaultModelName}'");
                        return Command::FAILURE;
                    }
                } catch (\Throwable $e) {
                    $io->newLine();
                    $io->error('Download failed: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        }

        // Summary
        $io->section('Summary');

        if ($results['passed']) {
            $io->success('All required checks passed! FluentVox is ready to use.');
            return Command::SUCCESS;
        }

        $failedChecks = array_filter(
            $results['checks'],
            fn($check) => !$check['status'] && !(isset($check['optional']) && $check['optional'])
        );

        if (!empty($failedChecks)) {
            $io->error('Some required checks failed. Please fix the issues above.');
            $io->text('Run <info>vendor/bin/fluentvox install</info> to install missing dependencies.');
            return Command::FAILURE;
        }

        $io->warning('All required checks passed, but some optional features are unavailable.');
        return Command::SUCCESS;
    }
}
