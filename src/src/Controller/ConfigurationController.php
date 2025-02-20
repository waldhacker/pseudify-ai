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

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\BasicConfigurationType;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto\ColumnConfigurationDtoFactory;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\ColumnConfigurationType;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\TableConfigurationType;
use Waldhacker\Pseudify\Core\Gui\Processing\ColumnProcessor;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\EncodingsGuesser;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\GuesserContextFactory;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\MeaningGuesser;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionFactory;

/**
 * @internal
 */
class ConfigurationController extends AbstractController
{
    use AppContextTrait;

    public const int ITEMS_PER_PAGE = 10;

    private string $autoConfigurationLogFile = '';

    public function __construct(
        private ConnectionManager $connectionManager,
        private Repository $repository,
        private ColumnProcessor $columnProcessor,
        private ColumnConfigurationDtoFactory $columnConfigurationDtoFactory,
        private readonly EncodingsGuesser $encodingsGuesser,
        private readonly MeaningGuesser $meaningGuesser,
        private readonly GuesserContextFactory $guesserContextFactory,
        private readonly AdvancedEncoderCollection $encoderCollection,
        private readonly ProfileDefinitionFactory $profileDefinitionFactory,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        protected ParameterBagInterface $params,
        private readonly Filesystem $filesystem,
        private string $logDirectory,
        private string $appHome,
    ) {
        $this->logDirectory = rtrim($logDirectory, '/');
        $this->filesystem->mkdir($this->logDirectory);
        $this->autoConfigurationLogFile = sprintf('%s/autoConfiguration.log', $this->logDirectory);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/basic', name: 'app_configuration_basic')]
    public function basicConfiguration(Request $request): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        $profileDefinitionForm = $this->createForm(BasicConfigurationType::class);
        $profileDefinitionForm->handleRequest($request);
        if ($profileDefinitionForm->isSubmitted() && $profileDefinitionForm->isValid()) {
            $data = $profileDefinitionForm->getData();

            $activeProfile
                ->setIdentifier($data['identifier'])
                ->setDescription($data['description'])
                ->setApplicationName($data['applicationName'])
                ->setApplicationDescription($data['applicationDescription'])
                ->setTargetDataFrameCuttingLength($data['targetDataFrameCuttingLength'] ?? 10)
                ->setSourceStrings($data['sourceStrings'])
                ->setExcludedTargetColumnTypes($data['excludedTargetColumnTypes'])
                ->setExcludedTargetTables($data['excludedTargetTables'])
            ;

            $session = $this->requestStack->getSession();
            $session->set('activeProfileHasUpdates', true);
        }

        return $this->render('configuration/basic.html.twig', [
            'context' => $this->getAppContext(),
            'profileDefinitionForm' => $profileDefinitionForm,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/table', name: 'app_configuration_table')]
    public function tableConfiguration(): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        return $this->render('configuration/table.html.twig', [
            'context' => $this->getAppContext(),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/table-include/{tableName}', name: 'app_configuration_table_include')]
    public function includeTable(string $tableName): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        $activeProfile->removeExcludedTargetTable($tableName);

        $session = $this->requestStack->getSession();
        $session->set('activeProfileHasUpdates', true);

        return $this->redirectToRoute('app_configuration_table');
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/table-exclude/{tableName}', name: 'app_configuration_table_exclude')]
    public function excludeTable(string $tableName): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        if ($activeProfile->tableExists($tableName)) {
            $activeProfile->addExcludedTargetTable($tableName);

            $session = $this->requestStack->getSession();
            $session->set('activeProfileHasUpdates', true);
        }

        return $this->redirectToRoute('app_configuration_table');
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/table/{tableName}', name: 'app_configuration_table_edit')]
    public function editTable(string $tableName, Request $request): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);
        $session = $this->requestStack->getSession();

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        if (!$activeProfile->tableExists($tableName)) {
            return $this->redirectToRoute('app_configuration_table');
        }
        $table = $activeProfile->getTable($tableName);

        $tableConfigurationForm = $this->createForm(TableConfigurationType::class, $table);
        $tableConfigurationForm->handleRequest($request);

        if ($tableConfigurationForm->isSubmitted() && $tableConfigurationForm->isValid()) {
            $session->set('activeProfile', $activeProfile);
            $session->set('activeProfileHasUpdates', true);

            return $this->redirectToRoute('app_configuration_table_edit', ['tableName' => $tableName]);
        }

        return $this->render('configuration/singleTable.html.twig', [
            'context' => $this->getAppContext(),
            'table' => $table,
            'tableConfigurationForm' => $tableConfigurationForm,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/column-include/{tableName}/{columnName}', name: 'app_configuration_column_include')]
    public function includeColumn(string $tableName, string $columnName): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        if (!$activeProfile->columnExists($tableName, $columnName)) {
            return $this->redirectToRoute('app_configuration_table');
        }

        $activeProfile->getTable($tableName)?->removeExcludedTargetColumn($columnName);

        $session = $this->requestStack->getSession();
        $session->set('activeProfileHasUpdates', true);

        return $this->redirectToRoute('app_configuration_table');
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/column-exclude/{tableName}/{columnName}', name: 'app_configuration_column_exclude')]
    public function excludeColumn(string $tableName, string $columnName): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        if (!$activeProfile->columnExists($tableName, $columnName)) {
            return $this->redirectToRoute('app_configuration_table');
        }

        $activeProfile->getTable($tableName)?->addExcludedTargetColumn($columnName);

        $session = $this->requestStack->getSession();
        $session->set('activeProfileHasUpdates', true);

        return $this->redirectToRoute('app_configuration_table');
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/table/{tableName}/{columnName}', name: 'app_configuration_column_edit')]
    public function editColumn(
        string $tableName,
        string $columnName,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);
        $session = $this->requestStack->getSession();

        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        if (!$activeProfile->columnExists($tableName, $columnName)) {
            return $this->redirectToRoute('app_configuration_table');
        }
        $column = $activeProfile->getColumn($tableName, $columnName);

        $columnConfigurationForm = $this->createForm(ColumnConfigurationType::class, $this->columnConfigurationDtoFactory->fromColumn($column));
        $columnConfigurationForm->handleRequest($request);

        if ($columnConfigurationForm->isSubmitted() && $columnConfigurationForm->isValid()) {
            $data = $columnConfigurationForm->getData();

            $column
                ->setColumnDescription($data->columnDescription ?? '')
                ->setEncodings($this->columnConfigurationDtoFactory->dtoToEncodings($data))
                ->setMeanings($this->columnConfigurationDtoFactory->dtoToMeanings($data))
            ;

            $session->set('activeProfile', $activeProfile);
            $session->set('activeProfileHasUpdates', true);

            return $this->redirectToRoute('app_configuration_column_edit', ['tableName' => $tableName, 'columnName' => $columnName]);
        }

        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate(
            $this->repository->getFindAllQueryBuilder($tableName),
            $page,
            self::ITEMS_PER_PAGE
        );

        $processDatabaseRows = $this->columnProcessor->processDatabaseRows($column, $pagination->getItems());

        return $this->render('configuration/column.html.twig', [
            'context' => $this->getAppContext(),
            'table' => $activeProfile->getTable($tableName),
            'column' => $column,
            'columnConfigurationForm' => $columnConfigurationForm,
            'processDatabaseRows' => $processDatabaseRows,
            'pagination' => $pagination,
            'page' => $page,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/guess/encoding', name: 'app_configuration_guess_encoding')]
    public function guessEncoding(
        #[MapQueryParameter] string $tableName,
        #[MapQueryParameter] string $columnName,
        #[MapQueryParameter] int $columnIndex,
        #[MapQueryParameter] int $page,
        #[MapQueryParameter] bool $original,
        PaginatorInterface $paginator,
    ): Response {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (null === $activeProfile || !$activeProfile->columnExists($tableName, $columnName)) {
            return $this->json([
                'status' => 'error',
                'data' => 'Unknown table or column',
            ]);
        }

        $table = $activeProfile->getTable($tableName);
        $column = $activeProfile->getColumn($tableName, $columnName);

        $pagination = $paginator->paginate(
            $this->repository->getFindAllQueryBuilder($tableName),
            $page,
            self::ITEMS_PER_PAGE
        );

        $databaseRow = $pagination->getItems()[$columnIndex] ?? null;
        if (null === $databaseRow) {
            return $this->json([
                'status' => 'error',
                'data' => 'Error while guessing',
            ]);
        }

        $decodedColumnData = $this->columnProcessor->processDatabaseRow($column, $databaseRow);
        $data = $original ? $decodedColumnData['original'] : $decodedColumnData['decoded'];

        $guesserContext = $this->guesserContextFactory->fromProfileDefinition($activeProfile, $table, $column);
        try {
            $possibleEncoders = $this->encodingsGuesser->guess([$data], $guesserContext, 1);
        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'data' => 'Error while guessing',
                'error' => $e->getMessage(),
            ]);
        }

        $guessedEncoders = [];
        foreach ($possibleEncoders as $possibleEncoder) {
            $encoder = $possibleEncoder['encoder'] ?? null;
            if (!$encoder) {
                continue;
            }

            $guessedEncoders[] = [
                'name' => $this->encoderCollection->getEncoderShortName($encoder),
                'context' => $possibleEncoder['context'] ?? [],
            ];
        }

        return $this->json([
            'status' => 'ok',
            'data' => $guessedEncoders,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/guess/meaning', name: 'app_configuration_guess_meaning')]
    public function guessMeaning(
        #[MapQueryParameter] string $tableName,
        #[MapQueryParameter] string $columnName,
        #[MapQueryParameter] int $columnIndex,
        #[MapQueryParameter] int $page,
        PaginatorInterface $paginator,
    ): Response {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile?->columnExists($tableName, $columnName)) {
            return $this->json([
                'status' => 'error',
                'data' => 'Unknown table or column',
            ]);
        }

        $table = $activeProfile->getTable($tableName);
        $column = $activeProfile->getColumn($tableName, $columnName);

        $pagination = $paginator->paginate(
            $this->repository->getFindAllQueryBuilder($tableName),
            $page,
            self::ITEMS_PER_PAGE
        );

        $databaseRow = $pagination->getItems()[$columnIndex] ?? null;
        if (null === $databaseRow) {
            return $this->json([
                'status' => 'error',
                'data' => 'Error while guessing',
            ]);
        }

        $decodedColumnData = $this->columnProcessor->processDatabaseRow($column, $databaseRow);
        unset($decodedColumnData['context'], $decodedColumnData['meanings']);

        $guesserContext = $this->guesserContextFactory->fromProfileDefinition($activeProfile, $table, $column);
        try {
            $possibleMeanings = $this->meaningGuesser->guess([$decodedColumnData], $guesserContext, 1);
        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'data' => 'Error while guessing',
                'error' => $e->getMessage(),
            ]);
        }

        $guessedMeanings = [];
        foreach ($possibleMeanings as $possibleMeaning) {
            $guessedMeanings[] = [
                'path' => $possibleMeaning->getProperty()->getPath() ?? '',
                'type' => $possibleMeaning->getProperty()->getType() ?? '',
            ];
        }

        return $this->json([
            'status' => 'ok',
            'data' => $guessedMeanings,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/auto', name: 'app_configuration_auto')]
    public function autoConfiguration(): Response
    {
        $activeProfile = $this->getAppContext()['activeProfile'];
        if (!$activeProfile) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        return $this->render('configuration/auto.html.twig', [
            'context' => $this->getAppContext(),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/auto/run', name: 'app_configuration_auto_run')]
    public function doAutoConfiguration(): Response
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
            $this->filesystem->remove($this->autoConfigurationLogFile);
            $process = Process::fromShellCommandline(
                command: sprintf(
                    'bin/pseudify pseudify:autoconfiguration %s --connection %s',
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
                    $this->filesystem->appendToFile($this->autoConfigurationLogFile, $data);
                }
                sleep(1);
            }
        } finally {
            $this->filesystem->remove($this->autoConfigurationLogFile);
        }
        try {
            $profileDefinition = $this->profileDefinitionFactory->load($activeProfile->getIdentifier(), $this->getAppContext()['activeConnectionName'], true);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $this->translator->trans(sprintf('Profile "%s" does not exist. Message: "%s".', $activeProfile->getIdentifier(), $e->getMessage())),
            ]);
        }
        $session = $this->requestStack->getSession();
        $session->set('profileDefinition', $profileDefinition);
        $session->set('activeProfileHasUpdates', false);

        return $this->json([
            'status' => 'done',
            'message' => $stdOut,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/configuration/auto/fetchlog', name: 'app_configuration_auto_fetch_log')]
    public function fetchAnalyzeLog(): Response
    {
        if (!$this->filesystem->exists($this->autoConfigurationLogFile)) {
            return $this->json([
                'status' => 'no-data',
                'message' => null,
            ]);
        }

        return $this->json([
            'status' => 'stream',
            'message' => file_get_contents($this->autoConfigurationLogFile),
        ]);
    }
}
