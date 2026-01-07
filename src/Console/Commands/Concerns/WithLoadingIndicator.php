<?php

declare(strict_types=1);

namespace B7s\FluentVox\Console\Commands\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait for commands that need to display loading indicators.
 */
trait WithLoadingIndicator
{
    /**
     * Execute a callback with a loading indicator.
     *
     * @param callable $callback The operation to execute
     * @param string $message The loading message
     * @param OutputInterface $output The output interface
     * @param SymfonyStyle $io The Symfony style helper
     * @return mixed The result of the callback
     */
    protected function withLoading(callable $callback, string $message, OutputInterface $output, SymfonyStyle $io): mixed
    {
        if ($output->isVerbose()) {
            return $callback();
        }

        $io->write("{$message}... ");
        $result = $callback();
        $output->write("\r" . str_repeat(' ', strlen($message) + 4) . "\r");

        return $result;
    }

    /**
     * Execute a callback with a progress bar for multiple items.
     *
     * @param array $items Items to process
     * @param callable $callback Callback that receives (item, progressBar) and returns result
     * @param string $message The loading message
     * @param OutputInterface $output The output interface
     * @param SymfonyStyle $io The Symfony style helper
     * @return array Results indexed by item key
     */
    protected function withProgressBar(array $items, callable $callback, string $message, OutputInterface $output, SymfonyStyle $io): array
    {
        $results = [];

        if (!$output->isVerbose() && count($items) > 0) {
            $progressBar = new ProgressBar($output, count($items));
            $progressBar->setFormat(' %current%/%max% [%bar%] %message%');
            $progressBar->setMessage($message);
            $progressBar->start();

            foreach ($items as $key => $item) {
                $progressBar->setMessage("{$message} ({$key})...");
                $results[$key] = $callback($item, $progressBar);
                $progressBar->advance();
            }

            $progressBar->setMessage('Done');
            $progressBar->finish();
            $io->newLine(2);
        } else {
            foreach ($items as $key => $item) {
                $results[$key] = $callback($item, null);
            }
        }

        return $results;
    }

    /**
     * Show a simple spinner while executing a callback.
     *
     * @param callable $callback The operation to execute
     * @param string $message The loading message
     * @param OutputInterface $output The output interface
     * @return mixed The result of the callback
     */
    protected function withSpinner(callable $callback, string $message, OutputInterface $output): mixed
    {
        if ($output->isVerbose()) {
            return $callback();
        }

        $spinner = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $spinnerIndex = 0;
        $running = true;

        // Start spinner in background (simplified version)
        $output->write("{$message}... ");

        try {
            $result = $callback();
        } finally {
            $output->write("\r" . str_repeat(' ', strlen($message) + 4) . "\r");
        }

        return $result;
    }
}
