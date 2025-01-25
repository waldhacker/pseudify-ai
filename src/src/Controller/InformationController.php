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

use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionCollection;

/**
 * @internal
 */
class InformationController extends AbstractController
{
    use AppContextTrait;

    public function __construct(
        private RequestStack $requestStack,
        protected ParameterBagInterface $params,
    ) {
    }

    #[\Symfony\Component\Routing\Attribute\Route('/index', name: 'app_information_information')]
    public function information(
        ProfileDefinitionCollection $profileDefinitionCollection,
        ConditionExpressionProvider $conditionExpressionProvider,
        AdvancedEncoderCollection $encoderCollection,
        ConnectionManager $connectionManager,
    ): Response {
        $session = $this->requestStack->getSession();
        $activeProfile = $session->get('profileDefinition', null);

        $profileDefinitions = [];
        foreach (array_map(fn (string $profileIdentifier): ProfileDefinition => $profileDefinitionCollection->getProfileDefinition($profileIdentifier), $profileDefinitionCollection->getProfileDefinitionIdentifiers()) as $profileDefinition) {
            $profileDefinitions[$profileDefinition->getIdentifier()] = [
                'identifier' => $profileDefinition->getIdentifier(),
                'description' => $profileDefinition->getDescription(),
                'path' => strstr((string) $profileDefinition->getPath(), 'src/Profiles/Yaml'),
                'active' => $activeProfile?->getIdentifier() === $profileDefinition->getIdentifier(),
            ];
        }

        $dataProcessingFunctions = $conditionExpressionProvider->getFunctionInformation();
        ksort($dataProcessingFunctions);

        $encoders = array_keys($encoderCollection->getEncodersIndexedByShortName());

        $databaseDrivers = [
            'MySQL' => [
                'pdo_mysql' => ['A MySQL driver that uses the pdo_mysql PDO extension', empty(phpversion('pdo_mysql')) ? 'N/A' : phpversion('pdo_mysql')],
                'mysqli' => ['A MySQL driver that uses the mysqli extension', empty(phpversion('mysqli')) ? 'N/A' : phpversion('mysqli')],
            ],
            'PostgreSQL' => [
                'pdo_pgsql' => ['A PostgreSQL driver that uses the pdo_pgsql PDO extension', empty(phpversion('pdo_pgsql')) ? 'N/A' : phpversion('pdo_pgsql')],
            ],
            'SQLite' => [
                'pdo_sqlite' => ['An SQLite driver that uses the pdo_sqlite PDO extension', empty(phpversion('pdo_sqlite')) ? 'N/A' : phpversion('pdo_sqlite')],
                'sqlite3' => ['An SQLite driver that uses the sqlite3 extension', empty(phpversion('sqlite3')) ? 'N/A' : phpversion('sqlite3')],
            ],
            'SQL Server' => [
                'pdo_sqlsrv' => ['A Microsoft SQL Server driver that uses pdo_sqlsrv PDO', empty(phpversion('pdo_sqlsrv')) ? 'N/A' : phpversion('pdo_sqlsrv')],
                'sqlsrv' => ['A Microsoft SQL Server driver that uses the sqlsrv PHP extension', empty(phpversion('sqlsrv')) ? 'N/A' : phpversion('sqlsrv')],
            ],
            'Oracle Database' => [
                'pdo_oci' => ['An Oracle driver that uses the pdo_oci PDO extension (not recommended by doctrine)', empty(phpversion('pdo_oci')) ? 'N/A' : phpversion('pdo_oci')],
                'oci8' => ['An Oracle driver that uses the oci8 PHP extension', empty(phpversion('oci8')) ? 'N/A' : phpversion('oci8')],
            ],
            'IBM DB2' => [
                'pdo_ibm' => ['An DB2 driver that uses the pdo_ibm PHP extension', empty(phpversion('pdo_ibm')) ? 'N/A' : phpversion('pdo_ibm')],
                'ibm_db2' => ['An DB2 driver that uses the ibm_db2 extension', empty(phpversion('ibm_db2')) ? 'N/A' : phpversion('ibm_db2')],
            ],
        ];

        $connectionInformation = [];
        foreach ($connectionManager->getConnections() as $connectionName => $connection) {
            $platform = $connection->getDatabasePlatform();
            $doctrineTypeMappings = [];
            foreach ((new \ReflectionClass($platform))->getProperty('doctrineTypeMapping')->getValue($platform) as $databaseType => $doctrineTypeName) {
                $doctrineTypeMappings[$databaseType] = [
                    'databaseType' => $databaseType,
                    'doctrineTypeName' => $doctrineTypeName,
                    'doctrineTypeImplementation' => Type::getType($doctrineTypeName)::class,
                ];
            }
            ksort($doctrineTypeMappings);

            $connectionInformation[$connectionName] = [
                'connectionName' => $connectionName,
                'doctrineTypeMappings' => $doctrineTypeMappings,
                'implementations' => [
                    'connectionImplementation' => $connection::class,
                    'driverImplementation' => $connection->getDriver()::class,
                    'databasePlatformImplementation' => $platform::class,
                ],
            ];
        }
        ksort($connectionInformation);

        $envConfiguration = [
            'db_driver' => $this->params->get('db_driver'),
            'db_host' => $this->params->get('db_host'),
            'db_port' => $this->params->get('db_port'),
            'db_user' => $this->params->get('db_user'),
            'db_password' => empty($this->params->get('db_password')) ? '<empty>' : '*****',
            'db_dbname' => $this->params->get('db_dbname'),
            'db_charset' => $this->params->get('db_charset'),
            'db_version' => $this->params->get('db_version'),
            'db_ssl_insecure' => $this->params->get('db_ssl_insecure'),
            'faker_locale' => $this->params->get('faker_locale'),
        ];

        $doctrineTypeImplementations = array_map(static fn (Type $type): array => [$type->getName(), $type::class], Type::getTypeRegistry()->getMap());
        ksort($doctrineTypeImplementations);

        $information = [
            'profileDefinitions' => $profileDefinitions,
            'envConfiguration' => $envConfiguration,
            'dataProcessingFunctions' => $dataProcessingFunctions,
            'encoders' => $encoders,
            'doctrineTypeImplementations' => $doctrineTypeImplementations,
            'databaseDrivers' => $databaseDrivers,
            'connectionInformation' => $connectionInformation,
        ];

        return $this->render('information/information.html.twig', [
            'context' => $this->getAppContext(),
            'information' => $information,
        ]);
    }
}
