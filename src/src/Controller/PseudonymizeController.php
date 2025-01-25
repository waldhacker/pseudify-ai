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

namespace Waldhacker\Pseudify\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class PseudonymizeController extends AbstractController
{
    use AppContextTrait;

    private string $analyzeLogFile = '';
    private string $pseudonymizeLogFile = '';

    public function __construct(
        private RequestStack $requestStack,
        protected ParameterBagInterface $params,
        private TranslatorInterface $translator,
        private readonly Filesystem $filesystem,
        private string $logDirectory,
        private string $appHome,
    ) {
        $this->logDirectory = rtrim($logDirectory, '/');
        $this->filesystem->mkdir($this->logDirectory);
        $this->analyzeLogFile = sprintf('%s/analyze.log', $this->logDirectory);
        $this->pseudonymizeLogFile = sprintf('%s/pseudonymize.log', $this->logDirectory);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/pseudonymize/analyze', name: 'app_pseudonymize_analyze')]
    public function analyze(): Response
    {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        return $this->render('pseudonymize/analyze.html.twig', [
            'context' => $this->getAppContext(),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/pseudonymize/analyze/run', name: 'app_pseudonymize_analyze_run')]
    public function doAnalyze(): Response
    {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            return $this->json([
                'status' => 'error',
                'message' => $this->translator->trans('No profile is activated. Please activate a profile first.'),
            ]);
        }
        $stdOut = '';
        try {
            $this->filesystem->remove($this->analyzeLogFile);
            $process = Process::fromShellCommandline(
                command: sprintf(
                    'bin/pseudify pseudify:analyze %s --connection %s',
                    escapeshellarg($activeProfile->getIdentifier()),
                    escapeshellarg($this->getAppContext()['activeConnectionName'])
                ),
                cwd: $this->appHome,
                timeout: null
            );
            $process->setTty(false)->start();

            while ($process->isRunning()) {
                foreach ($process as $type => $data) {
                    $stdOut .= $data;
                    $this->filesystem->appendToFile($this->analyzeLogFile, $data);
                }
                sleep(1);
            }
        } finally {
            $this->filesystem->remove($this->analyzeLogFile);
        }

        return $this->json([
            'status' => 'done',
            'message' => $stdOut,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/pseudonymize/analyze/fetchlog', name: 'app_pseudonymize_analyze_fetch_log')]
    public function fetchAnalyzeLog(): Response
    {
        if (!$this->filesystem->exists($this->analyzeLogFile)) {
            return $this->json([
                'status' => 'no-data',
                'message' => null,
            ]);
        }

        return $this->json([
            'status' => 'stream',
            'message' => file_get_contents($this->analyzeLogFile),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/pseudonymize/pseudonymize', name: 'app_pseudonymize_pseudonymize')]
    public function pseudonymize(): Response
    {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        return $this->render('pseudonymize/pseudonymize.html.twig', [
            'context' => $this->getAppContext(),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/pseudonymize/pseudonymize/run', name: 'app_pseudonymize_pseudonymize_run')]
    public function doPseudonymize(): Response
    {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            return $this->json([
                'status' => 'error',
                'message' => $this->translator->trans('No profile is activated. Please activate a profile first.'),
            ]);
        }
        $stdOut = '';
        try {
            $this->filesystem->remove($this->pseudonymizeLogFile);
            $process = Process::fromShellCommandline(
                command: sprintf(
                    'bin/pseudify pseudify:pseudonymize %s --connection %s -v --parallel --concurrency 100',
                    escapeshellarg($activeProfile->getIdentifier()),
                    escapeshellarg($this->getAppContext()['activeConnectionName'])
                ),
                cwd: $this->appHome,
                timeout: null
            );
            $process->setTty(false)->start();

            while ($process->isRunning()) {
                foreach ($process as $type => $data) {
                    $stdOut .= $data;
                    $this->filesystem->appendToFile($this->pseudonymizeLogFile, $data);
                }
                sleep(1);
            }
        } finally {
            $this->filesystem->remove($this->pseudonymizeLogFile);
        }

        return $this->json([
            'status' => 'done',
            'message' => $stdOut,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/pseudonymize/pseudonymize/fetchlog', name: 'app_pseudonymize_pseudonymize_fetch_log')]
    public function fetchPseudonymizeLog(): Response
    {
        if (!$this->filesystem->exists($this->pseudonymizeLogFile)) {
            return $this->json([
                'status' => 'no-data',
                'message' => null,
            ]);
        }

        return $this->json([
            'status' => 'stream',
            'message' => file_get_contents($this->pseudonymizeLogFile),
        ]);
    }
}
