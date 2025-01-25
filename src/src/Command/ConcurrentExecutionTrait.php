<?php

declare(strict_types=1);

/*
 * This file is part of the pseudify database pseudonymizer project
 * - (c) 2025 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Waldhacker\Pseudify\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

trait ConcurrentExecutionTrait
{
    /**
     * @param array<int, string>     $arguments
     * @param array<string, string>  $env
     * @param array<int, int|string> $identifierPool
     */
    protected function executeConcurrent(
        InputInterface $input,
        OutputInterface $output,
        string $command,
        array $arguments = [],
        array $identifierPool = [''],
        int $concurrency = 1,
        bool $appendVerbosityFlag = true,
        ?bool $useTty = null,
        ?string $cwd = null,
        ?array $env = null,
        ?callable $identifierPoolFiller = null,
        ?callable $onTick = null,
        ?callable $onStdOut = null,
    ): void {
        $io = new SymfonyStyle($input, $output);
        if ($appendVerbosityFlag) {
            $verbosityFlag = match ($output->getVerbosity()) {
                OutputInterface::VERBOSITY_QUIET => '-q',
                OutputInterface::VERBOSITY_VERBOSE => '-v',
                OutputInterface::VERBOSITY_VERY_VERBOSE => '-vv',
                OutputInterface::VERBOSITY_DEBUG => '-vvv',
                default => null,
            };

            $arguments[] = $verbosityFlag;
        }

        $identifierChunk = array_splice($identifierPool, 0, $concurrency);
        if (null !== $identifierPoolFiller) {
            $identifierPool = array_merge($identifierPool, $identifierPoolFiller($concurrency, true));
        }
        $runningProcesses = [];

        $executeProcesses = function (array $identifiers) use (&$runningProcesses, $command, $arguments, $cwd, $env, $useTty, $onStdOut): void {
            foreach ($identifiers as $identifier) {
                $fullCommand = array_merge(
                    [$command],
                    array_map(
                        fn (string $argument): string => str_replace(['{{ identifier }}'], [$identifier], $argument),
                        array_filter($arguments)
                    )
                );

                // $this->logger->debug(PHP_EOL.'Run '.implode(' ', $fullCommand));
                $process = Process::fromShellCommandline(command: implode(' ', $fullCommand), cwd: $cwd, env: $env, timeout: null);
                $process->setTty(null === $onStdOut ? ($useTty ?? Process::isTtySupported()) : false)->start();
                $runningProcesses[] = $process;
            }
        };

        $executeProcesses($identifierChunk);

        $tickInterval = 0.250000;
        $nextTick = microtime(true) + $tickInterval;

        while (count($runningProcesses)) {
            foreach ($runningProcesses as $processIndex => $runningProcess) {
                $stdout = $runningProcess->getOutput();
                $stderr = $runningProcess->getErrorOutput();
                if ($stdout) {
                    if (null !== $onStdOut) {
                        $onStdOut($stdout);
                    }
                    $this->logger->debug($stdout);
                }
                if ($stderr) {
                    $this->logger->debug($stderr);
                }

                if (!$runningProcess->isRunning()) {
                    unset($runningProcesses[$processIndex]);
                }
            }

            $identifierChunkCount = $concurrency - count($runningProcesses);

            if ($identifierChunkCount > 0) {
                $identifierChunk = array_splice($identifierPool, 0, $identifierChunkCount);
                if (null !== $identifierPoolFiller && count($identifierChunk)) {
                    $identifierPool = array_merge($identifierPool, $identifierPoolFiller($identifierChunkCount, false));
                }

                $executeProcesses($identifierChunk);
            }

            if (null !== $onTick && microtime(true) >= $nextTick) {
                $onTick();
                $nextTick = microtime(true) + $tickInterval;
            }
        }
    }
}
