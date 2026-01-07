<?php

declare(strict_types=1);

namespace B7s\FluentVox\Console\Commands;

use B7s\FluentVox\Support\Platform;
use B7s\FluentVox\Support\PythonRunner;
use B7s\FluentVox\Support\RequirementsChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Install Chatterbox TTS and dependencies.
 */
#[AsCommand(
    name: 'install',
    description: 'Install Chatterbox TTS and required dependencies',
)]
class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('pytorch', null, InputOption::VALUE_NONE, 'Also install/upgrade PyTorch')
            ->addOption('upgrade', 'u', InputOption::VALUE_NONE, 'Upgrade existing packages')
            ->addOption('cpu-only', null, InputOption::VALUE_NONE, 'Install CPU-only version (no CUDA)')
            ->setHelp(<<<'HELP'
The <info>install</info> command installs Chatterbox TTS and its dependencies.

<info>Basic installation:</info>
  vendor/bin/fluentvox install

<info>Install with PyTorch:</info>
  vendor/bin/fluentvox install --pytorch

<info>Upgrade existing installation:</info>
  vendor/bin/fluentvox install --upgrade

<info>CPU-only installation (no CUDA):</info>
  vendor/bin/fluentvox install --cpu-only
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('FluentVox Installation');

        // Show platform info
        $platformInfo = Platform::info();
        $io->section('Platform');
        $io->text([
            sprintf('OS: %s (%s)', $platformInfo['os_name'], $platformInfo['architecture']),
            sprintf('PHP: %s', $platformInfo['php_version']),
        ]);

        $checker = new RequirementsChecker();

        // Check Python first
        $io->section('Checking Python');
        $pythonCheck = $checker->checkPython();

        if (!$pythonCheck['status']) {
            $io->error($pythonCheck['message']);
            return Command::FAILURE;
        }

        $io->success($pythonCheck['message']);

        // Install PyTorch if requested or not installed
        if ($input->getOption('pytorch')) {
            $io->section('Installing PyTorch');

            $pytorchCheck = $checker->checkPyTorch();
            if (!$pytorchCheck['status'] || $input->getOption('upgrade')) {
                $io->text('Installing PyTorch (this may take a while)...');

                $result = $checker->installPyTorch(function (string $data, bool $isError) use ($output) {
                    if ($output->isVerbose()) {
                        $output->write($data);
                    }
                });

                if (!$result['success']) {
                    $io->error('Failed to install PyTorch');
                    if (isset($result['error'])) {
                        $io->text('');
                        $io->text('<comment>Error details:</comment>');
                        $io->text($result['error']);
                    }
                    return Command::FAILURE;
                }

                $io->success('PyTorch installed successfully');
            } else {
                $io->success($pytorchCheck['message'] . ' (already installed)');
            }
        }

        // Install Chatterbox
        $io->section('Installing Chatterbox TTS');

        $chatterboxCheck = $checker->checkChatterbox();
        $needsInstall = !$chatterboxCheck['status'] || $input->getOption('upgrade');

        if ($needsInstall) {
            $action = $input->getOption('upgrade') ? 'Upgrading' : 'Installing';
            $io->text("{$action} Chatterbox TTS (this may take a while)...");
            
            // Show pip command being executed for better debugging
            if ($output->isVerbose()) {
                $pythonPath = $checker->getPythonPath();
                $command = $input->getOption('upgrade') 
                    ? "{$pythonPath} -m pip install --upgrade chatterbox-tts"
                    : "{$pythonPath} -m pip install chatterbox-tts";
                $io->text("<comment>Executing: {$command}</comment>");
            }

            $method = $input->getOption('upgrade') ? 'upgradeChatterbox' : 'installChatterbox';
            $result = $checker->$method(function (string $data, bool $isError) use ($output, $io) {
                if ($output->isVerbose()) {
                    $output->write($data);
                } elseif ($isError) {
                    // Always show errors, even in non-verbose mode
                    $io->text('<error>' . trim($data) . '</error>');
                }
            });

            if (!$result['success']) {
                $io->error('Failed to install Chatterbox TTS');
                $io->newLine();
                
                if (isset($result['error'])) {
                    $io->section('Error Details');
                    $io->text($result['error']);
                    $io->newLine();
                }
                
                // Provide troubleshooting tips
                $io->section('Troubleshooting');
                $io->text([
                    'Common issues and solutions:',
                    '',
                    '1. <info>Permission denied:</info> Try running with sudo or use a virtual environment',
                    '2. <info>Network issues:</info> Check your internet connection and pip mirrors',
                    '3. <info>Python version:</info> Ensure Python 3.10+ is installed',
                    '4. <info>Dependencies:</info> Some dependencies may need to be installed separately',
                    '',
                    'Try running manually:',
                    sprintf('  <comment>%s -m pip install chatterbox-tts</comment>', $checker->getPythonPath()),
                    '',
                    'For more details, run with <info>--verbose</info> flag:',
                    '  <comment>vendor/bin/fluentvox install --verbose</comment>',
                ]);
                
                return Command::FAILURE;
            }

            $io->success('Chatterbox TTS installed successfully');
        } else {
            $io->success($chatterboxCheck['message'] . ' (already installed)');
        }

        // Final verification
        $io->section('Verification');
        $results = $checker->check();

        foreach ($results['checks'] as $name => $check) {
            $isOptional = isset($check['optional']) && $check['optional'];
            $status = $check['status'] ? '✓' : ($isOptional ? '○' : '✗');
            $io->text(sprintf(' %s %s: %s', $status, ucfirst($name), $check['message']));
        }

        if ($results['passed']) {
            $io->newLine();
            $io->success('FluentVox is ready to use!');

            $io->text([
                'Quick start:',
                '',
                '  use B7s\\FluentVox\\FluentVox;',
                '',
                '  $result = FluentVox::make()',
                '      ->text("Hello, world!")',
                '      ->generate();',
            ]);

            return Command::SUCCESS;
        }

        $io->warning('Some requirements are not met. Please fix the issues above.');
        return Command::FAILURE;
    }
}
