<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Application\Command;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Waldhacker\Pseudify\Core\Kernel;

/**
 * Application RED/GREEN contract for Issue #1 unique email handling.
 *
 * The test DB user only has SELECT/UPDATE. Table + seed rows come from
 * engine data_pseudify SQL fixtures (wh_unique_email_issue).
 */
class UniqueEmailPseudonymizeCommandTest extends KernelTestCase
{
    private const TABLE = 'wh_unique_email_issue';
    private const COLLISION_EMAIL = 'dup@example.com';

    /** @var array<int, string> */
    private const ORIGINAL_EMAILS = [
        1 => 'alice.one@example.com',
        2 => 'bob.two@example.com',
        3 => 'carol.three@example.com',
        4 => 'dave.four@example.com',
    ];

    protected function setUp(): void
    {
        $envFile = __DIR__.'/../../.test/.env';
        if (!is_file($envFile)) {
            self::markTestSkipped('Missing src/tests/.test/.env — run prepare-test-environment / AI-App env setup first.');
        }

        // overload: bootstrap already registered empty PSEUDIFY_DATABASE_* from
        // src/.env; loadEnv would keep those empties and workers would fall back
        // to userdata/.env (wrong host) when spawned via Process.
        $dotenv = new Dotenv();
        $dotenv->overload($envFile);
        $this->exportDatabaseEnvironment();
    }

    public function testSequentialWithoutUniqueFailsOnUniqueConstraint(): void
    {
        $kernel = self::bootKernel(['environment' => 'test']);
        $connection = self::getContainer()->get(Connection::class);
        $this->assertIssueTableReady($connection);
        $this->resetIssueEmails($connection);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find('pseudify:pseudonymize'));

        try {
            $commandTester->execute(['profile' => 'unique_email_collision_off'], ['decorated' => false]);
            $this->fail('Expected UNIQUE constraint failure without unique flag');
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        } catch (\Doctrine\DBAL\Exception $exception) {
            $message = strtolower($exception->getMessage());
            $this->assertTrue(
                str_contains($message, 'duplicate')
                || str_contains($message, 'unique constraint')
                || str_contains($message, 'unique key')
                || str_contains($message, '23000'),
                sprintf('Expected DB unique/duplicate failure, got: %s', $exception->getMessage())
            );
        }
    }

    public function testSequentialWithUniqueProducesDistinctNonOriginalEmailsAndClearsCaches(): void
    {
        $kernel = self::bootKernel(['environment' => 'test']);
        $connection = self::getContainer()->get(Connection::class);
        $this->assertIssueTableReady($connection);
        $this->resetIssueEmails($connection);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find('pseudify:pseudonymize'));

        $exitCode = $commandTester->execute(['profile' => 'unique_email_collision_on'], ['decorated' => false]);
        $this->assertSame(Command::SUCCESS, $exitCode);

        $rows = $this->fetchIssueRows($connection);
        $this->assertCount(count(self::ORIGINAL_EMAILS), $rows);

        $emails = array_map(static fn (array $row): string => (string) $row['email'], $rows);
        $this->assertSame(count($emails), count(array_unique($emails)), 'emails must be distinct');

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $this->assertArrayHasKey($id, self::ORIGINAL_EMAILS);
            $this->assertNotSame(self::ORIGINAL_EMAILS[$id], $row['email']);
        }

        $this->assertNotSame(
            array_fill(0, count($emails), self::COLLISION_EMAIL),
            $emails,
            'not every row may be dup@example.com'
        );

        $this->assertCacheRootsEmpty();
    }

    public function testParallelWithUniqueProducesDistinctEmailsWithoutGuard(): void
    {
        StaticDriver::setKeepStaticConnections(false);

        $kernel = self::bootKernel(['environment' => 'test']);
        $connection = self::getContainer()->get(Connection::class);

        try {
            $this->assertIssueTableReady($connection);
            $this->resetIssueEmails($connection);

            putenv('APP_ENV=test');
            $_SERVER['APP_ENV'] = 'test';
            $_ENV['APP_ENV'] = 'test';
            $this->exportDatabaseEnvironment();

            $application = new Application($kernel);
            $commandTester = new CommandTester($application->find('pseudify:pseudonymize'));
            // Drop the parent connection so we do not keep a REPEATABLE READ
            // snapshot across worker commits (subprocess updates).
            $connection->close();

            $exitCode = $commandTester->execute([
                'profile' => 'unique_email_collision_on',
                '--parallel' => true,
                '--concurrency' => '2',
                '--items-per-process' => '1',
            ], ['decorated' => false]);

            $display = $commandTester->getDisplay(true);
            $this->assertSame(Command::SUCCESS, $exitCode, $display);
            $this->assertDoesNotMatchRegularExpression(
                '/(unique.+(not supported|unsupported|forbidden|disabled)|cannot.+unique.+parallel|parallel.+unique.+(not|unsupported|forbidden))/i',
                $display
            );

            $connection = self::getContainer()->get(Connection::class);
            $rows = $this->fetchIssueRows($connection);
            $this->assertCount(count(self::ORIGINAL_EMAILS), $rows);

            $emails = array_map(static fn (array $row): string => (string) $row['email'], $rows);
            $this->assertSame(count($emails), count(array_unique($emails)));
            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $this->assertNotSame(
                    self::ORIGINAL_EMAILS[$id],
                    $row['email'],
                    sprintf('row %d still original; all rows=%s; display=%s', $id, json_encode($emails), $display)
                );
            }
            $this->assertNotSame(
                array_fill(0, count($emails), self::COLLISION_EMAIL),
                $emails
            );
            $this->assertCacheRootsEmpty();
        } finally {
            $this->resetIssueEmails($connection);
            StaticDriver::setKeepStaticConnections(true);
        }
    }

    private function assertIssueTableReady(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();
        $this->assertContains(
            self::TABLE,
            $schemaManager->listTableNames(),
            'Fixture table wh_unique_email_issue missing — re-import tests/*/data_pseudify*.sql'
        );

        $count = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($connection->quoteIdentifier(self::TABLE))
            ->executeQuery()
            ->fetchOne();
        $this->assertGreaterThanOrEqual(4, $count);
    }

    private function resetIssueEmails(Connection $connection): void
    {
        foreach (self::ORIGINAL_EMAILS as $id => $email) {
            $connection->update(self::TABLE, ['email' => $email], ['id' => $id]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchIssueRows(Connection $connection): array
    {
        return $connection->createQueryBuilder()
            ->select('*')
            ->from($connection->quoteIdentifier(self::TABLE))
            ->orderBy('id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function assertCacheRootsEmpty(): void
    {
        $fakeDataDirectory = Kernel::USER_DATA_PATH.'/var/cache/pseudonymize_fakedata';
        $claimDirectory = Kernel::USER_DATA_PATH.'/var/cache/pseudonymize_unique';

        foreach ([$fakeDataDirectory, $claimDirectory] as $directory) {
            $this->assertTrue(
                !is_dir($directory) || [] === array_diff(scandir($directory) ?: [], ['.', '..']),
                sprintf('Expected empty cache root: %s', $directory)
            );
        }
    }

    private function exportDatabaseEnvironment(): void
    {
        // Prefer $_ENV over $_SERVER: bootEnv may leave empty PSEUDIFY_DATABASE_*
        // in $_SERVER while .test/.env values live in $_ENV only.
        foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }
            if (!str_starts_with($key, 'PSEUDIFY_DATABASE_') && 'APP_ENV' !== $key) {
                continue;
            }
            $stringValue = (string) $value;
            if ('' === $stringValue && str_starts_with($key, 'PSEUDIFY_DATABASE_')) {
                continue;
            }
            putenv($key.'='.$stringValue);
            $_SERVER[$key] = $stringValue;
            $_ENV[$key] = $stringValue;
        }

        putenv('APP_ENV=test');
        $_SERVER['APP_ENV'] = 'test';
        $_ENV['APP_ENV'] = 'test';
    }
}
