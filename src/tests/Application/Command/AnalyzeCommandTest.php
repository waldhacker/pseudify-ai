<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Application\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Waldhacker\Pseudify\Core\Command\InvalidArgumentException;

class AnalyzeCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $expected = [
            0 => 'Decode and dump source data',
            1 => '===========================',
            2 => 'Decode and dump target data',
            3 => '===========================',
            4 => 'Prepare data files',
            5 => '==================',
            6 => 'Grep within target data',
            7 => '=======================',
            8 => 'Process grepped target data',
            9 => '===========================',
            10 => 'Prepare summary',
            11 => '===============',
            12 => 'summary',
            13 => '=======',
            14 => '------------------------------ ---------------------------------------------------------------------------------------------- ------------------------',
            15 => 'source                         data                                                                                           seems to be in',
            16 => '------------------------------ ---------------------------------------------------------------------------------------------- ------------------------',
            17 => '__custom__.__custom__          Homenick                                                                                       wh_log.log_data',
            18 => '__custom__.__custom__          lafayette64@example.net                                                                        wh_log.log_data',
            19 => '__custom__.__custom__          Homenick                                                                                       wh_log.log_message',
            20 => 'wh_user.city                   Dorothyfort                                                                                    wh_meta_data.meta_data',
            21 => 'wh_user.city                   North Elenamouth                                                                               wh_meta_data.meta_data',
            22 => 'wh_user.city                   South Wilfordland                                                                              wh_meta_data.meta_data',
            23 => 'wh_user.email                  mcclure.ofelia@example.com                                                                     wh_log.log_data',
            24 => 'wh_user.email                  mcclure.ofelia@example.com                                                                     wh_log.log_message',
            25 => 'wh_user.email                  cassin.bernadette@example.net                                                                  wh_meta_data.meta_data',
            26 => 'wh_user.email                  conn.abigale@example.net                                                                       wh_meta_data.meta_data',
            27 => 'wh_user.email                  mcclure.ofelia@example.com                                                                     wh_meta_data.meta_data',
            28 => 'wh_user.first_name             Donato                                                                                         wh_meta_data.meta_data',
            29 => 'wh_user.first_name             Maybell                                                                                        wh_meta_data.meta_data',
            30 => 'wh_user.first_name             Mckayla                                                                                        wh_meta_data.meta_data',
            31 => 'wh_user.last_name              Keeling                                                                                        wh_log.log_data',
            32 => 'wh_user.last_name              Anderson                                                                                       wh_meta_data.meta_data',
            33 => 'wh_user.last_name              Keeling                                                                                        wh_meta_data.meta_data',
            34 => 'wh_user.last_name              Stoltenberg                                                                                    wh_meta_data.meta_data',
            35 => 'wh_user.password               $argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U   wh_meta_data.meta_data',
            36 => 'wh_user.password               $argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q   wh_meta_data.meta_data',
            37 => 'wh_user.password               $argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs   wh_meta_data.meta_data',
            38 => 'wh_user.username               georgiana59                                                                                    wh_log.log_data',
            39 => 'wh_user.username               georgiana59                                                                                    wh_log.log_message',
            40 => 'wh_user.username               georgiana59                                                                                    wh_meta_data.meta_data',
            41 => 'wh_user.username               howell.damien                                                                                  wh_meta_data.meta_data',
            42 => 'wh_user.username               hpagac                                                                                         wh_meta_data.meta_data',
            43 => 'wh_user_session.session_data   1321:57fc:460b:d4d0:d83f:c200:4b:f1c8                                                          wh_log.ip',
            44 => 'wh_user_session.session_data   4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98                                                         wh_log.ip',
            45 => 'wh_user_session.session_data   1321:57fc:460b:d4d0:d83f:c200:4b:f1c8                                                          wh_log.log_data',
            46 => 'wh_user_session.session_data   4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98                                                         wh_log.log_data',
            47 => 'wh_user_session.session_data   1321:57fc:460b:d4d0:d83f:c200:4b:f1c8                                                          wh_meta_data.meta_data',
            48 => 'wh_user_session.session_data   197.110.248.18                                                                                 wh_meta_data.meta_data',
            49 => 'wh_user_session.session_data   244.166.32.78                                                                                  wh_meta_data.meta_data',
            50 => '------------------------------ ---------------------------------------------------------------------------------------------- ------------------------',
        ];

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $command = $application->find('pseudify:analyze');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'test'], ['decorated' => false, 'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE, 'capture_stderr_separately' => true]);

        $output = $commandTester->getDisplay(true);
        $output = array_values(array_map('trim', array_filter(explode("\n", $output))));

        foreach ($output as $index => $data) {
            $this->assertStringContainsString($expected[$index], $data);
        }
    }

    public function testExecuteWithEmptySourceDataCollection()
    {
        $expected = [
            0 => 'Decode and dump source data',
            1 => '===========================',
            2 => 'Decode and dump target data',
            3 => '===========================',
            4 => 'Prepare data files',
            5 => '==================',
            6 => 'Grep within target data',
            7 => '=======================',
            8 => 'Process grepped target data',
            9 => '===========================',
            10 => 'Prepare summary',
            11 => '===============',
            12 => 'summary',
            13 => '=======',
            14 => 'no data found',
        ];

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $command = $application->find('pseudify:analyze');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'nop'], ['decorated' => false, 'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE, 'capture_stderr_separately' => true]);

        $output = $commandTester->getDisplay(true);
        $output = array_values(array_map('trim', array_filter(explode("\n", $output))));

        foreach ($output as $index => $data) {
            $this->assertStringContainsString($expected[$index], $data);
        }
    }

    public function testExecuteThrowsExceptionOnMissingProfile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1619890696);

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $container = self::getContainer();

        $command = $application->find('pseudify:analyze');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'missing']);
    }
}
