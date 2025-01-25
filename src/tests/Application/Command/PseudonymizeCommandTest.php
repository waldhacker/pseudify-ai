<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Application\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Waldhacker\Pseudify\Core\Command\InvalidArgumentException;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\MissingTableException;

class PseudonymizeCommandTest extends KernelTestCase
{
    public function testExecuteWithDryRunOptionOutputsDatabaseQueries()
    {
        $expected = [
            [':dcValue1:\'gbailey\'', ':dcValue2:1'],
            [':dcValue1:\'{+-0pBNvYgx\'', ':dcValue2:1'],
            [':dcValue1:\'Mya\'', ':dcValue2:1'],
            [':dcValue1:\'DuBuque\'', ':dcValue2:1'],
            [':dcValue1:\'gilbert32@marquardt.com\'', ':dcValue2:1'],
            [':dcValue1:\'Lake Haven\'', ':dcValue2:1'],
            [':dcValue1:\'leo34\'', ':dcValue2:2'],
            [':dcValue1:\'~=NG5a:x&S$hSn7L\\\'ff2\'', ':dcValue2:2'],
            [':dcValue1:\'Rosina\'', ':dcValue2:2'],
            [':dcValue1:\'Wisoky\'', ':dcValue2:2'],
            [':dcValue1:\'micaela88@yahoo.com\'', ':dcValue2:2'],
            [':dcValue1:\'Batzbury\'', ':dcValue2:2'],
            [':dcValue1:\'cynthia.fahey\'', ':dcValue2:3'],
            [':dcValue1:\'u.*,JHRp_B)L\\\'(?aiG;o\'', ':dcValue2:3'],
            [':dcValue1:\'Chelsea\'', ':dcValue2:3'],
            [':dcValue1:\'Smith\'', ':dcValue2:3'],
            [':dcValue1:\'cmoen@gmail.com\'', ':dcValue2:3'],
            [':dcValue1:\'Moiseschester\'', ':dcValue2:3'],
            [':dcValue1:\'edna59\'', ':dcValue2:4'],
            [':dcValue1:\'urSUOn8}S\'', ':dcValue2:4'],
            [':dcValue1:\'Katlynn\'', ':dcValue2:4'],
            [':dcValue1:\'Carter\'', ':dcValue2:4'],
            [':dcValue1:\'nkreiger@klocko.com\'', ':dcValue2:4'],
            [':dcValue1:\'Stantontown\'', ':dcValue2:4'],
            [':dcValue1:\'pearline37\'', ':dcValue2:5'],
            [':dcValue1:\'6iP3LaPHHN7\\\\\'', ':dcValue2:5'],
            [':dcValue1:\'Lori\'', ':dcValue2:5'],
            [':dcValue1:\'Greenholt\'', ':dcValue2:5'],
            [':dcValue1:\'harber.audie@lakin.com\'', ':dcValue2:5'],
            [':dcValue1:\'East Reid\'', ':dcValue2:5'],
            [':dcValue1:\'a:1:{s:7:"last_ip";s:13:"2.150.225.140";}\'', ':dcValue2:1'],
            [':dcValue1:\'a:1:{s:7:"last_ip";s:15:"204.246.218.201";}\'', ':dcValue2:2'],
            [':dcValue1:\'a:1:{s:7:"last_ip";s:13:"239.216.59.19";}\'', ':dcValue2:3'],
            [':dcValue1:\'a:1:{s:7:"last_ip";s:15:"156.181.203.225";}\'', ':dcValue2:4'],
            [':dcValue1:\'a:1:{s:7:"last_ip";s:12:"109.15.7.123";}\'', ':dcValue2:5'],
            [':dcValue1:\'1f8b08000000000000036551cb4ec4300cfc15940f084dd207750f7059d8c30aade08a5459d4d08836ad92a0dd0af5df494a2b1671f3c4e3f17882a0e0cb410aec8326c12a843262094c37acd290550e6e807d3ab2067b62018a04d848683b6d4815ec8730a273a7c1360b214ce7faa80e78dcef1f8b97e5ed8253b7e8dada4f23adc368df072375f38f3876a88da7b35f3a0a98b9cfaa24bf35eef9daec36336fda3a5f6ff6c22987c1ea5896c03abc6805fc60894c3b748b62068c7ad45dac65002dd91e0d47d3583addd119fbb1236ec8afbaafda4fabce2ee85e3d51f43c6ff1c9189ffc139f5ae370e49c1e4cdda0c7454c84ab41446eb19ad4e376a454259722e759c94519f42f56a8b842fcfe58bacc845a8894ab840b9973510423f3fc0d15ebd7bfda010000\'', ':dcValue2:1'],
            [':dcValue1:\'1f8b08000000000000036551cb4ec33010fc15e40f30b59d47b339c0a5d043852ab822452bb225168913d9466d84f2efd8211145dc76bcb3b3b36304055f0e12601f340a562214114b60ba66a586b474b005f6e9c81aec88052836c00642db6a432a673f84019d3bf7b69e09613ad34775c0e37eff94bfce6f579caa41d7547e1c681946fbde1ba9eb7fc4a1456d3c5dfcdc51c0cc435a6eb23be35e6ecd6e3573d2d6f96ab5174e39f456c7b200d6e2552be0474b649abe9d155360d4a16e632d0368c87668389adad2f99e2ed80d2d71437ed17dd37e5c747641f7e699a2e7698d4fc6f8e49ff8d4128723e7746faa1a3dce62225c0d2272f3c5a41ed623a52ab814194f0b2e8aa07fb542c515e2f7c7927926d462abb894094f722ed2c09aa66faf1a276fda010000\'', ':dcValue2:2'],
            [':dcValue1:\'1f8b080000000000000365514d6bc42014fc2ffe0089e6fbe552e8a1941e7a0885d24b78acb62b4d4c50cb6e68f3dffb4c131a58f0e038e39b714448e1db4306ec53cf82350875c4129851ac3190351e2a605f5e3b8b8366040b605a59cc6bf6c74de8fd65742ac29aa4ae7d79b6d5d2462c0e7c77467feec23ce9ed22ba8fd14aa36e84538fc6067d0d2b93d0cc87b7c7d30fbe666a3f7937ce876e8f54027bc2d0cfd66e217a3cb014f81e5dd02e829cd20f68fab8970298426b748f5572a7af384cbde656afbe54c9c9847935245d1bd08691d6853c96bd31191b93378d09425e7b6f46db290cb8ce4be9c520a2b6dc029a699d4e91445e7051092e93944b9993c3c1248d26e2ff9b32b65988a2e422e7928ba424fdf20b4fbaab7bcd010000\'', ':dcValue2:3'],
            [':dcValue1:\'1f8b08000000000000036551c14e843010fd15d30fa8b4854586835e56f7b0311bbd9a90898cd20885b435bbc4f0efb60891646ff33a6fdebc794550f0e32005f645a360254211b104a66b566ac84a0777c0be1d59831db10045026c20b4ad36a472f64718d0b9736feb9910a677faa48e783a1c9ef3b7f96dc3a91a744de5c7819661b49fbd91babe220e2d6ae3e9e2e78e02661eb332d9dd1bf77a6bf6ab990f6d9daf567be194636f752c0b602d6e5a013f5922d3f4edac9801a30e751b6b194043b643c3d1d496ce0f74c16e68891bf28beebbf6e3a2b30fba372f143d4f6b7c32c627afe28b7138724ef7a6aad1e32c166c2388c8cd17937a58b31349c145c6732ea40af29b0d2a6e10ff1f96aeb98830a104cf532ed360639a7e01a001b98ad8010000\'', ':dcValue2:4'],
            [':dcValue1:\'1f8b08000000000000036551c94ec33010fd17736011b26a27a5c9e400824301c1851e3944a3665a8fc826db4023d47fc70e8da8d49b9fe62d33cf0809fc3848417cd0a044819047ac4170250a86a4709081f874645b6c4804a81210eba1f586516ed0d020fe383d3af7ddd92a423d0b1a7975fdfcf8d697f7972fe717b7c8cba21bf547dcd2a033a51ffad1790102edb66b359ff0fa1ab9f5b4f387b0f7ea6b599d6d9f466208dbb075be9c560c460f866a4718510ea2c6a3e91cc4aa616f0e6f6a90ebd127109b0607b2fe8e76d8f435c9ce6ee328f4b3663f4ce7bf76ecc8ad0d394f5614fba9401d0bd42705aa8002df71d79615fa71a95485634145eee2b01ff793bf4e72a9d58d9ce752e5c1ff28228911eaffcfd25113ce503a913acda49e05511668fbfd2f9a68e29edd010000\'', ':dcValue2:5'],
            [':dcValue1:\'65794a3163325679546d46745a534936496d4a765a3246754c6d646c62334a6e6157467559534973496d567459576c73496a6f6962573168655756796445426c654746746347786c4c6d39795a794973496d78686333524f5957316c496a6f6955323170644767694c434a7063434936496a45314e6934784f4445754d6a417a4c6a49794e534a39\'', ':dcValue2:1'],
            [':dcValue1:\'{"message":"foo text \\\\"bogan.georgiana\\\\", another \\\\"mmayert@example.org\\\\""}\'', ':dcValue2:1'],
            [':dcValue1:\'aaa:3a02:69d7:216d:60d4:3923:5b12:c14c\'', ':dcValue2:1'],
            [':dcValue1:\'65794a3163325679546d46745a534936496d5276636e4a70637935726247396a613238694c434a6c6257467062434936496d747062474a685932737561325675626d46415a586868625842735a53356a623230694c434a7359584e30546d46745a534936496b5a6c615777694c434a7063434936496a67754d5455774c6a49304d6934324d534a39\'', ':dcValue2:2'],
            [':dcValue1:\'{"message":"foo text \\\\"dorris.klocko\\\\", another \\\\"kilback.kenna@example.com\\\\""}\'', ':dcValue2:2'],
            [':dcValue1:\'8.150.242.61\'', ':dcValue2:2'],
            [':dcValue1:\'613a323a7b693a303b733a31323a223135322e392e3130332e3937223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a373a226c656c61683031223b733a383a226c6173744e616d65223b733a363a224272616b7573223b733a353a22656d61696c223b733a32383a226b72697374696e2e6c696e646772656e406578616d706c652e6e6574223b733a323a226964223b693a31303b733a343a2275736572223b523a333b7d7d\'', ':dcValue2:3'],
            [':dcValue1:\'{"message":"bar text \\\\"Brakus\\\\", another \\\\"lelah01\\\\""}\'', ':dcValue2:3'],
            [':dcValue1:\'33a3:80a4:3654:ad3d:7a1c:6d01:970b:1abe\'', ':dcValue2:3'],
            [':dcValue1:\'613a323a7b693a303b733a31343a223137342e3139302e33342e313435223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a363a2265646e613539223b733a383a226c6173744e616d65223b733a363a2248616d6d6573223b733a353a22656d61696c223b733a32303a22646f7269616e3736406578616d706c652e6f7267223b733a323a226964223b693a323b733a343a2275736572223b523a333b7d7d\'', ':dcValue2:4'],
            [':dcValue1:\'{"message":"bar text \\\\"Hammes\\\\", another \\\\"edna59\\\\""}\'', ':dcValue2:4'],
            [':dcValue1:\'230.108.40.13\'', ':dcValue2:4'],
            [':dcValue1:\'613a323a7b693a303b733a31323a2237332e3131392e37312e3936223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a31343a226f6e647269636b612e646f6e6e61223b733a383a226c6173744e616d65223b733a373a224b6573736c6572223b733a353a22656d61696c223b733a32343a2274797265652e626c616e6461406578616d706c652e6e6574223b733a323a226964223b693a39313b733a343a2275736572223b523a333b7d7d\'', ':dcValue2:5'],
            [':dcValue1:\'{"message":"bar text \\\\"Kessler\\\\", another \\\\"ondricka.donna\\\\""}\'', ':dcValue2:5'],
            [':dcValue1:\'146.198.147.164\'', ':dcValue2:5'],
        ];

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $command = $application->find('pseudify:pseudonymize');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'test', '--dry-run' => true], ['decorated' => false]);

        $contents = file(__DIR__.'/../../../../userdata/var/log/pseudify_dry_run.log');

        foreach ($contents as $index => $sql) {
            foreach ($expected[$index] as $expectedString) {
                $this->assertStringContainsString($expectedString, $sql);
            }
        }
    }

    public function testExecuteReplacesDatabaseValues()
    {
        $expected = [
            'wh_user' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'username' => 'karl13',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$amo3Z28zNTlwZG84TG1YZg$1Ka95oewxn3xs/jLrTN0R9lhIxtNnQynBFRdE/70cAQ',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => '6bJ=yq',
                        'first_name' => 'Jordyn',
                        'last_name' => 'Shields',
                        'email' => 'madaline30@example.net',
                        'city' => 'Lake Tanner',
                    ],
                    'processed' => [
                        'id' => 1,
                        'username' => 'gbailey',
                        'password' => '{+-0pBNvYgx',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => '6bJ=yq',
                        'first_name' => 'Mya',
                        'last_name' => 'DuBuque',
                        'email' => 'gilbert32@marquardt.com',
                        'city' => 'Lake Haven',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'username' => 'reilly.chase',
                        'password' => '$2y$04$O0XKmRw3wl9mni55dSEJXuj3vygjCgdyUviihec.PTiTAu2SS/C6u',
                        'password_hash_type' => 'bcrypt',
                        'password_plaintext' => 'wHiDoIBY<6Up',
                        'first_name' => 'Keenan',
                        'last_name' => 'King',
                        'email' => 'johns.percy@example.com',
                        'city' => 'Edwardotown',
                    ],
                    'processed' => [
                        'id' => 2,
                        'username' => 'leo34',
                        'password' => '~=NG5a:x&S$hSn7L\'ff2',
                        'password_hash_type' => 'bcrypt',
                        'password_plaintext' => 'wHiDoIBY<6Up',
                        'first_name' => 'Rosina',
                        'last_name' => 'Wisoky',
                        'email' => 'micaela88@yahoo.com',
                        'city' => 'Batzbury',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'username' => 'hpagac',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U',
                        'password_hash_type' => 'argon2i',
                        'password_plaintext' => '[dvGd#gI',
                        'first_name' => 'Donato',
                        'last_name' => 'Keeling',
                        'email' => 'mcclure.ofelia@example.com',
                        'city' => 'North Elenamouth',
                    ],
                    'processed' => [
                        'id' => 3,
                        'username' => 'cynthia.fahey',
                        'password' => 'u.*,JHRp_B)L\'(?aiG;o',
                        'password_hash_type' => 'argon2i',
                        'password_plaintext' => '[dvGd#gI',
                        'first_name' => 'Chelsea',
                        'last_name' => 'Smith',
                        'email' => 'cmoen@gmail.com',
                        'city' => 'Moiseschester',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'username' => 'georgiana59',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => 'uGZIc|aX4d',
                        'first_name' => 'Maybell',
                        'last_name' => 'Anderson',
                        'email' => 'cassin.bernadette@example.net',
                        'city' => 'South Wilfordland',
                    ],
                    'processed' => [
                        'id' => 4,
                        'username' => 'edna59',
                        'password' => 'urSUOn8}S',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => 'uGZIc|aX4d',
                        'first_name' => 'Katlynn',
                        'last_name' => 'Carter',
                        'email' => 'nkreiger@klocko.com',
                        'city' => 'Stantontown',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'username' => 'howell.damien',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => 'nF5;06?nsS/nE',
                        'first_name' => 'Mckayla',
                        'last_name' => 'Stoltenberg',
                        'email' => 'conn.abigale@example.net',
                        'city' => 'Dorothyfort',
                    ],
                    'processed' => [
                        'id' => 5,
                        'username' => 'pearline37',
                        'password' => '6iP3LaPHHN7\\',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => 'nF5;06?nsS/nE',
                        'first_name' => 'Lori',
                        'last_name' => 'Greenholt',
                        'email' => 'harber.audie@lakin.com',
                        'city' => 'East Reid',
                    ],
                ],
            ],
            'wh_user_session' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'session_data' => 'a:1:{s:7:"last_ip";s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";}',
                    ],
                    'processed' => [
                        'id' => 1,
                        'session_data' => 'a:1:{s:7:"last_ip";s:13:"2.150.225.140";}',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'session_data' => 'a:1:{s:7:"last_ip";s:13:"107.66.23.195";}',
                    ],
                    'processed' => [
                        'id' => 2,
                        'session_data' => 'a:1:{s:7:"last_ip";s:15:"204.246.218.201";}',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'session_data' => 'a:1:{s:7:"last_ip";s:13:"244.166.32.78";}',
                    ],
                    'processed' => [
                        'id' => 3,
                        'session_data' => 'a:1:{s:7:"last_ip";s:13:"239.216.59.19";}',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'session_data' => 'a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}',
                    ],
                    'processed' => [
                        'id' => 4,
                        'session_data' => 'a:1:{s:7:"last_ip";s:15:"156.181.203.225";}',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'session_data' => 'a:1:{s:7:"last_ip";s:14:"197.110.248.18";}',
                    ],
                    'processed' => [
                        'id' => 5,
                        'session_data' => 'a:1:{s:7:"last_ip";s:12:"109.15.7.123";}',
                    ],
                ],
            ],
            'wh_meta_data' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'meta_data' => '1f8b080000000000000365525d4f023110fc2fcd3d9a4a7bc7570951236234011340455fc8c215aea1d7d66b112e86ff6e7b7211e3db4e77ba33d32db0987d599630b4e525413d60dd80294322453dc19a3dcb3a0ced2c2f14e41c7948628632bde752e21472c115fae118b076af8b34c0ae1f1041b1d18a8ae8b34fba51deef5cb83eb9307d12bdcb347f7a4de96cf03859c6c3d9f8c5dc8f6fa2a7c9c3deb44abe9d1c1a34ff1825afb7b3974ccacddc3c9bddf0f19d8cf2e5dbfe6634b7958f33cd4506365bb8d2f093999376fa8f682408e5f8c1d551d4b0d96bb4ae949d5eaabbeab0c1d05a14d62deac46d8646ab2d9412aa6c0c4938eb12c2d0d469e9b85af262138e9a0cf11c840c35f56fbbd24a61588a0d487ecd0f901bc9b1e29587d016aeac270d74a15d56ae75e1bbc77a33346c86fed94c1c2e7864b9b542ab450aaeb297f821c048e0b64f4e85a9c3d224c1a4d5c231c5ed8e9f7f26110709f2fb1912741220711777086e605f78fef11bb387fddf33020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"139.81.0.139";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"139.81.0.139";}}',
                    ],
                    'processed' => [
                        'id' => 1,
                        'meta_data' => '1f8b08000000000000036551cb4ec4300cfc15940f084dd207750f7059d8c30aade08a5459d4d08836ad92a0dd0af5df494a2b1671f3c4e3f17882a0e0cb410aec8326c12a843262094c37acd290550e6e807d3ab2067b62018a04d848683b6d4815ec8730a273a7c1360b214ce7faa80e78dcef1f8b97e5ed8253b7e8dada4f23adc368df072375f38f3876a88da7b35f3a0a98b9cfaa24bf35eef9daec36336fda3a5f6ff6c22987c1ea5896c03abc6805fc60894c3b748b62068c7ad45dac65002dd91e0d47d3583addd119fbb1236ec8afbaafda4fabce2ee85e3d51f43c6ff1c9189ffc139f5ae370e49c1e4cdda0c7454c84ab41446eb19ad4e376a454259722e759c94519f42f56a8b842fcfe58bacc845a8894ab840b9973510423f3fc0d15ebd7bfda010000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"139.81.0.139";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:10:"pearline37";s:8:"password";s:12:"6iP3LaPHHN7\";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:4:"Lori";s:9:"last_name";s:9:"Greenholt";s:5:"email";s:25:"herman.andrew@example.net";s:4:"city";s:9:"East Reid";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"239.216.59.19";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"114.30.126.172";}}',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'meta_data' => '1f8b080000000000000365525b4fc23014fe2fcd1ecda4ddc6a5842851319a800920282fe4cc55d6d0b5752dc262f8efb693458c0f4d7a6edfa5a74023fa65684cd1965518f581f67c4c28e219ea739af40ded52b433ac945030e4421c5194ab3d1322cca0e04ca29f1e0dc6ec5599f9b0e7000228374a121e7c0e702f2806dd0b3bc0177a808395c88aa76546e6b78fd3341acd270b7d3f19064fd387bd6e576c3b3db448f1318e9737f3452ec4e6453febdde87185c745faba1f8e5f4cade38c739d83c9d7b6d2ec24e6c49dfd6bd402b8b4ec601b2b7294f45bed2b696697f2ae4eb6287ae7a5b1ebc67187a2f1db162a01b5378a049c5531a6686695b04ca6acdcf85442112b800b7f27ee6ddf949421a47c03825db303145ab050b25a832f735b3548b7aa5436afde55e9aac76633c46f86fcd94ce4075c6498315cc97506b696173b10a0d8f7764e4ab96ecc92380e71bb1d4624ec741dfe1945e429f0ef6788eb19e704773b218e9290443d7fdcc8f11b4093841d36020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:15:"187.135.239.239";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:15:"187.135.239.239";}}',
                    ],
                    'processed' => [
                        'id' => 2,
                        'meta_data' => '1f8b08000000000000036551cb4ec33010fc15e40f30b59d47b339c0a5d043852ab822452bb225168913d9466d84f2efd8211145dc76bcb3b3b36304055f0e12601f340a562214114b60ba66a586b474b005f6e9c81aec88052836c00642db6a432a673f84019d3bf7b69e09613ad34775c0e37eff94bfce6f579caa41d7547e1c681946fbde1ba9eb7fc4a1456d3c5dfcdc51c0cc435a6eb23be35e6ecd6e3573d2d6f96ab5174e39f456c7b200d6e2552be0474b649abe9d155360d4a16e632d0368c87668389adad2f99e2ed80d2d71437ed17dd37e5c747641f7e699a2e7698d4fc6f8e49ff8d4128723e7746faa1a3dce62225c0d2272f3c5a41ed623a52ab814194f0b2e8aa07fb542c515e2f7c7927926d462abb894094f722ed2c09aa66faf1a276fda010000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:15:"187.135.239.239";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:10:"pearline37";s:8:"password";s:12:"6iP3LaPHHN7\";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:4:"Lori";s:9:"last_name";s:9:"Greenholt";s:5:"email";s:25:"herman.andrew@example.net";s:4:"city";s:9:"East Reid";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"239.216.59.19";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"183.224.47.153";}}',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'meta_data' => '1f8b08000000000000036592dd6ea33010855f65657159116ca0818922f52fca6ea5d52a4bab46bd89066c821b302c769246ddbcfbda6ca246eae5f11cfb3be31984103e34444036e240c90421759a01919c4c2444130d0990ad16bdc246102b2905b2166dbf96a8304ec97f47875aefdb9e3b99daeb1ef6eb5631e9eda634f59a697265a6f4aa9b522f7b7e7c142faff36c3edb67ac624fb37dfcbcf636f78b261ac50f7fd4dbb21885995cee65639eee76f9c3aff47bf07b4bcbb8da2c76b3d1624871c15c55a8ab953974e214e6c4e65f8c5d8d5219f16e864a601b9bbffe28fee232e2e79352f6daaccecd8e81fcc4432eea7a680c488d1755fbf4ade2a2d7ad723206221a948395596f61b152f9b9fb3b2e8c1137e21d9bae16be124302fbed853487016d4959bb35d5b7175997366b8dca663a9e67c3dc6cd897d950abb4b09856ad381a744f5ddb1c08d479c7a7c0b27385d04a1a320af1b82c20ba0e72e0110f80276109050b02887228699158ee053a7468fab926d1790b58e0533f4e7c1ad92d381eff01fdaee8ae4c020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:4;s:8:"username";s:11:"georgiana59";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:10:"uGZIc|aX4d";s:10:"first_name";s:7:"Maybell";s:9:"last_name";s:8:"Anderson";s:5:"email";s:29:"cassin.bernadette@example.net";s:4:"city";s:17:"South Wilfordland";}s:4:"key2";a:2:{s:2:"id";i:4;s:12:"session_data";s:65:"a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}";}s:4:"key3";a:1:{s:4:"key4";s:11:"20.1.58.149";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:4;s:8:"username";s:11:"georgiana59";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:10:"uGZIc|aX4d";s:10:"first_name";s:7:"Maybell";s:9:"last_name";s:8:"Anderson";s:5:"email";s:29:"cassin.bernadette@example.net";s:4:"city";s:17:"South Wilfordland";}s:4:"key2";a:2:{s:2:"id";i:4;s:12:"session_data";s:65:"a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}";}s:4:"key3";a:1:{s:4:"key4";s:11:"20.1.58.149";}}',
                    ],
                    'processed' => [
                        'id' => 3,
                        'meta_data' => '1f8b080000000000000365514d6bc42014fc2ffe0089e6fbe552e8a1941e7a0885d24b78acb62b4d4c50cb6e68f3dffb4c131a58f0e038e39b714448e1db4306ec53cf82350875c4129851ac3190351e2a605f5e3b8b8366040b605a59cc6bf6c74de8fd65742ac29aa4ae7d79b6d5d2462c0e7c77467feec23ce9ed22ba8fd14aa36e84538fc6067d0d2b93d0cc87b7c7d30fbe666a3f7937ce876e8f54027bc2d0cfd66e217a3cb014f81e5dd02e829cd20f68fab8970298426b748f5572a7af384cbde656afbe54c9c9847935245d1bd08691d6853c96bd31191b93378d09425e7b6f46db290cb8ce4be9c520a2b6dc029a699d4e91445e7051092e93944b9993c3c1248d26e2ff9b32b65988a2e422e7928ba424fdf20b4fbaab7bcd010000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:4;s:8:"username";s:11:"georgiana59";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:10:"uGZIc|aX4d";s:10:"first_name";s:7:"Maybell";s:9:"last_name";s:8:"Anderson";s:5:"email";s:29:"cassin.bernadette@example.net";s:4:"city";s:17:"South Wilfordland";}s:4:"key2";a:2:{s:2:"id";i:4;s:12:"session_data";s:65:"a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}";}s:4:"key3";a:1:{s:4:"key4";s:11:"20.1.58.149";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:4;s:8:"username";s:6:"edna59";s:8:"password";s:9:"urSUOn8}S";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:10:"uGZIc|aX4d";s:10:"first_name";s:7:"Katlynn";s:9:"last_name";s:6:"Carter";s:5:"email";s:21:"daniela80@example.net";s:4:"city";s:11:"Stantontown";}s:4:"key2";a:2:{s:2:"id";i:4;s:12:"session_data";s:43:"a:1:{s:7:"last_ip";s:15:"156.181.203.225";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"167.15.2.107";}}',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'meta_data' => '1f8b08000000000000036552616bc23010fd2fa11fa5336dd51a299bcc3926e8409d3abfc8d5461b4c93ac89d332fcef4b3bcb043f047277efde7b970b109ffc68121074a005463d20dd32f6086209ea31d2ea69121274d4341790516443ec1394ca13e5dc4d206354a03f8c02ad4f324fcab06b091cc8f75278ccf98e70d7c9a2b06122dc501176d63cc9de9789371f8ca6b13f9c4f16ea75d277dea76f27d52ee8617a6e7ad9d738583ecf1729e7fb95fa50c7e1688dc759fc79ea8f57baf271a3b94941a71b53287a3573d54eee808a0313869e4d3d8a18b67acdf6a3d0b307f152259b04ed58aecda69eb843d0787b808243351b411c6eaa18133433921b2a629aefcb548b209a01e3e5ddb36fbb9542b810b33d70fa44cf90294e5d412b0f659999a2661ac85c9ab4d8c9dc562ff566bc7233dedd66b08d34d59a49b149c054f6029b03824b6ce7ea94a98add52e16ec7c5b8e97a41e8e2d00adc68f8a506feff0d41dd14fa166f0f6edbde96edb8fc020ca4e19b36020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:5;s:12:"session_data";s:42:"a:1:{s:7:"last_ip";s:14:"197.110.248.18";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"83.243.216.115";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:5;s:12:"session_data";s:42:"a:1:{s:7:"last_ip";s:14:"197.110.248.18";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"83.243.216.115";}}',
                    ],
                    'processed' => [
                        'id' => 4,
                        'meta_data' => '1f8b08000000000000036551c14e843010fd15d30fa8b4854586835e56f7b0311bbd9a90898cd20885b435bbc4f0efb60891646ff33a6fdebc794550f0e32005f645a360254211b104a66b566ac84a0777c0be1d59831db10045026c20b4ad36a472f64718d0b9736feb9910a677faa48e783a1c9ef3b7f96dc3a91a744de5c7819661b49fbd91babe220e2d6ae3e9e2e78e02661eb332d9dd1bf77a6bf6ab990f6d9daf567be194636f752c0b602d6e5a013f5922d3f4edac9801a30e751b6b194043b643c3d1d496ce0f74c16e68891bf28beebbf6e3a2b30fba372f143d4f6b7c32c627afe28b7138724ef7a6aad1e32c166c2388c8cd17937a58b31349c145c6732ea40af29b0d2a6e10ff1f96aeb98830a104cf532ed360639a7e01a001b98ad8010000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:5;s:12:"session_data";s:42:"a:1:{s:7:"last_ip";s:14:"197.110.248.18";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"83.243.216.115";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:10:"pearline37";s:8:"password";s:12:"6iP3LaPHHN7\";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:4:"Lori";s:9:"last_name";s:9:"Greenholt";s:5:"email";s:25:"herman.andrew@example.net";s:4:"city";s:9:"East Reid";}s:4:"key2";a:2:{s:2:"id";i:5;s:12:"session_data";s:40:"a:1:{s:7:"last_ip";s:12:"109.15.7.123";}";}s:4:"key3";a:1:{s:4:"key4";s:13:"19.131.74.242";}}',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'meta_data' => '1f8b080000000000000365915f4fc23014c5bf8aa97b240bedc6c64a480c22c428448828c10772dd0a6b5cd7ba967f1abebbedc448c25b4feeb9f7776e2fd0807e6b1a52f4c10e187580264e138a78863a9c061d4ddb146d34ab4a100c591951942b58438a7e6b0ab4dec92a7332b18d1e546b5912ee6dbb38f144b7dd305ddc505dec4de6e3f9fbf374b478dd0b56f6a6b3af6cb798783cca4b33eb3f42b0187c8adb61381835f953a5462acac27e7bbeaac63d73e88b9764e618f88cb9cc41e74b735075b298a213fbc2a70ae0a5617b730afd966d87d9f5fabe3636295af14a9be5d98a7d598291f54e14157056b49407c60a5eae9d6a51c404f0c2bd89ed13695a6c2ae6cb95b5c00ddb835005f353299cc3fe73cacda1a65af3585626bfba2b989d2d3726479de3df2d88bb05b9b805b64a33adb92c971918a88762bb37c5ce1b9fb2725523028a4818fa388afc80f871dbce3f43040e81ffcf1fa2138004894f62bf15fbd8c6381e7f00628e8a6d25020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:3;s:8:"username";s:6:"hpagac";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U";s:18:"password_hash_type";s:7:"argon2i";s:18:"password_plaintext";s:8:"[dvGd#gI";s:10:"first_name";s:6:"Donato";s:9:"last_name";s:7:"Keeling";s:5:"email";s:26:"mcclure.ofelia@example.com";s:4:"city";s:16:"North Elenamouth";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"239.27.57.12";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:3;s:8:"username";s:6:"hpagac";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U";s:18:"password_hash_type";s:7:"argon2i";s:18:"password_plaintext";s:8:"[dvGd#gI";s:10:"first_name";s:6:"Donato";s:9:"last_name";s:7:"Keeling";s:5:"email";s:26:"mcclure.ofelia@example.com";s:4:"city";s:16:"North Elenamouth";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"239.27.57.12";}}',
                    ],
                    'processed' => [
                        'id' => 5,
                        'meta_data' => '1f8b08000000000000036551c94ec33010fd17736011b26a27a5c9e400824301c1851e3944a3665a8fc826db4023d47fc70e8da8d49b9fe62d33cf0809fc3848417cd0a044819047ac4170250a86a4709081f874645b6c4804a81210eba1f586516ed0d020fe383d3af7ddd92a423d0b1a7975fdfcf8d697f7972fe717b7c8cba21bf547dcd2a033a51ffad1790102edb66b359ff0fa1ab9f5b4f387b0f7ea6b599d6d9f466208dbb075be9c560c460f866a4718510ea2c6a3e91cc4aa616f0e6f6a90ebd127109b0607b2fe8e76d8f435c9ce6ee328f4b3663f4ce7bf76ecc8ad0d394f5614fba9401d0bd42705aa8002df71d79615fa71a95485634145eee2b01ff793bf4e72a9d58d9ce752e5c1ff28228911eaffcfd25113ce503a913acda49e05511668fbfd2f9a68e29edd010000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:3;s:8:"username";s:6:"hpagac";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U";s:18:"password_hash_type";s:7:"argon2i";s:18:"password_plaintext";s:8:"[dvGd#gI";s:10:"first_name";s:6:"Donato";s:9:"last_name";s:7:"Keeling";s:5:"email";s:26:"mcclure.ofelia@example.com";s:4:"city";s:16:"North Elenamouth";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"239.27.57.12";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:3;s:8:"username";s:13:"cynthia.fahey";s:8:"password";s:20:"u.*,JHRp_B)L\'(?aiG;o";s:18:"password_hash_type";s:7:"argon2i";s:18:"password_plaintext";s:8:"[dvGd#gI";s:10:"first_name";s:7:"Chelsea";s:9:"last_name";s:5:"Smith";s:5:"email";s:19:"mmayert@example.org";s:4:"city";s:13:"Moiseschester";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"239.216.59.19";}";}s:4:"key3";a:1:{s:4:"key4";s:15:"123.248.209.183";}}',
                    ],
                ],
            ],
            'wh_log' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'log_type' => 'foo',
                        'log_data' => '65794a3163325679546d46745a534936496e4a76626d46735a4738784e534973496d567459576c73496a6f6962574e6a624856795a5335765a6d5673615746415a586868625842735a53356a623230694c434a7359584e30546d46745a534936496b746c5a577870626d63694c434a7063434936496a457a4d6a45364e54646d597a6f304e6a42694f6d51305a4441365a44677a5a6a706a4d6a41774f6a52694f6d5978597a676966513d3d',
                        'log_data_plaintext' => '{"userName":"ronaldo15","email":"mcclure.ofelia@example.com","lastName":"Keeling","ip":"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8"}',
                        'log_message' => '{"message":"foo text \"ronaldo15\", another \"mcclure.ofelia@example.com\""}',
                        'ip' => '1321:57fc:460b:d4d0:d83f:c200:4b:f1c8',
                        '_log_data' => '{"userName":"ronaldo15","email":"mcclure.ofelia@example.com","lastName":"Keeling","ip":"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8"}',
                    ],
                    'processed' => [
                        'id' => 1,
                        'log_type' => 'foo',
                        'log_data' => '65794a3163325679546d46745a534936496d4a765a3246754c6d646c62334a6e6157467559534973496d567459576c73496a6f6962573168655756796445426c654746746347786c4c6d39795a794973496d78686333524f5957316c496a6f6955323170644767694c434a7063434936496a45314e6934784f4445754d6a417a4c6a49794e534a39',
                        'log_data_plaintext' => '{"userName":"ronaldo15","email":"mcclure.ofelia@example.com","lastName":"Keeling","ip":"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8"}',
                        'log_message' => '{"message":"foo text \"bogan.georgiana\", another \"mmayert@example.org\""}',
                        'ip' => 'aaa:3a02:69d7:216d:60d4:3923:5b12:c14c',
                        '_log_data' => '{"userName":"bogan.georgiana","email":"mmayert@example.org","lastName":"Smith","ip":"156.181.203.225"}',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'log_type' => 'foo',
                        'log_data' => '65794a3163325679546d46745a534936496e4e3059584a724c6d70315a4751694c434a6c6257467062434936496e4e796233646c514756345957317762475575626d5630496977696247467a64453568625755694f694a4362336c6c63694973496d6c77496a6f694d5455314c6a49784e5334324e7934784f54456966513d3d',
                        'log_data_plaintext' => '{"userName":"stark.judd","email":"srowe@example.net","lastName":"Boyer","ip":"155.215.67.191"}',
                        'log_message' => '{"message":"foo text \"stark.judd\", another \"srowe@example.net\""}',
                        'ip' => '155.215.67.191',
                        '_log_data' => '{"userName":"stark.judd","email":"srowe@example.net","lastName":"Boyer","ip":"155.215.67.191"}',
                    ],
                    'processed' => [
                        'id' => 2,
                        'log_type' => 'foo',
                        'log_data' => '65794a3163325679546d46745a534936496d5276636e4a70637935726247396a613238694c434a6c6257467062434936496d747062474a685932737561325675626d46415a586868625842735a53356a623230694c434a7359584e30546d46745a534936496b5a6c615777694c434a7063434936496a67754d5455774c6a49304d6934324d534a39',
                        'log_data_plaintext' => '{"userName":"stark.judd","email":"srowe@example.net","lastName":"Boyer","ip":"155.215.67.191"}',
                        'log_message' => '{"message":"foo text \"dorris.klocko\", another \"kilback.kenna@example.com\""}',
                        'ip' => '8.150.242.61',
                        '_log_data' => '{"userName":"dorris.klocko","email":"kilback.kenna@example.com","lastName":"Feil","ip":"8.150.242.61"}',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a33383a223466623a313434373a646566623a396434373a613265303a613336613a313064333a66643938223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a31323a226672656964612e6d616e7465223b733a383a226c6173744e616d65223b733a353a2254726f6d70223b733a353a22656d61696c223b733a32333a226c61666179657474653634406578616d706c652e6e6574223b733a323a226964223b693a31303b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:12:"freida.mante";s:8:"lastName";s:5:"Tromp";s:5:"email";s:23:"lafayette64@example.net";s:2:"id";i:10;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Tromp\", another \"freida.mante\""}',
                        'ip' => '4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98',
                        '_log_data' => 'a:2:{i:0;s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:12:"freida.mante";s:8:"lastName";s:5:"Tromp";s:5:"email";s:23:"lafayette64@example.net";s:2:"id";i:10;s:4:"user";R:3;}}',
                    ],
                    'processed' => [
                        'id' => 3,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31323a223135322e392e3130332e3937223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a373a226c656c61683031223b733a383a226c6173744e616d65223b733a363a224272616b7573223b733a353a22656d61696c223b733a32383a226b72697374696e2e6c696e646772656e406578616d706c652e6e6574223b733a323a226964223b693a31303b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:12:"freida.mante";s:8:"lastName";s:5:"Tromp";s:5:"email";s:23:"lafayette64@example.net";s:2:"id";i:10;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Brakus\", another \"lelah01\""}',
                        'ip' => '33a3:80a4:3654:ad3d:7a1c:6d01:970b:1abe',
                        '_log_data' => 'a:2:{i:0;s:12:"152.9.103.97";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:7:"lelah01";s:8:"lastName";s:6:"Brakus";s:5:"email";s:28:"kristin.lindgren@example.net";s:2:"id";i:10;s:4:"user";R:3;}}',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31343a223234332e3230322e3234312e3637223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a31313a2267656f726769616e613539223b733a383a226c6173744e616d65223b733a353a22426c6f636b223b733a353a22656d61696c223b733a31393a226e6f6c616e3131406578616d706c652e6e6574223b733a323a226964223b693a323b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:14:"243.202.241.67";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:11:"georgiana59";s:8:"lastName";s:5:"Block";s:5:"email";s:19:"nolan11@example.net";s:2:"id";i:2;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Block\", another \"georgiana59\""}',
                        'ip' => '243.202.241.67',
                        '_log_data' => 'a:2:{i:0;s:14:"243.202.241.67";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:11:"georgiana59";s:8:"lastName";s:5:"Block";s:5:"email";s:19:"nolan11@example.net";s:2:"id";i:2;s:4:"user";R:3;}}',
                    ],
                    'processed' => [
                        'id' => 4,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31343a223137342e3139302e33342e313435223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a363a2265646e613539223b733a383a226c6173744e616d65223b733a363a2248616d6d6573223b733a353a22656d61696c223b733a32303a22646f7269616e3736406578616d706c652e6f7267223b733a323a226964223b693a323b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:14:"243.202.241.67";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:11:"georgiana59";s:8:"lastName";s:5:"Block";s:5:"email";s:19:"nolan11@example.net";s:2:"id";i:2;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Hammes\", another \"edna59\""}',
                        'ip' => '230.108.40.13',
                        '_log_data' => 'a:2:{i:0;s:14:"174.190.34.145";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:6:"edna59";s:8:"lastName";s:6:"Hammes";s:5:"email";s:20:"dorian76@example.org";s:2:"id";i:2;s:4:"user";R:3;}}',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31353a223133322e3138382e3234312e313535223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a373a22637972696c3036223b733a383a226c6173744e616d65223b733a383a22486f6d656e69636b223b733a353a22656d61696c223b733a32313a22636c696e746f6e3434406578616d706c652e6e6574223b733a323a226964223b693a39313b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:15:"132.188.241.155";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:7:"cyril06";s:8:"lastName";s:8:"Homenick";s:5:"email";s:21:"clinton44@example.net";s:2:"id";i:91;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Homenick\", another \"cyril06\""}',
                        'ip' => '132.188.241.155',
                        '_log_data' => 'a:2:{i:0;s:15:"132.188.241.155";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:7:"cyril06";s:8:"lastName";s:8:"Homenick";s:5:"email";s:21:"clinton44@example.net";s:2:"id";i:91;s:4:"user";R:3;}}',
                    ],
                    'processed' => [
                        'id' => 5,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31323a2237332e3131392e37312e3936223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a31343a226f6e647269636b612e646f6e6e61223b733a383a226c6173744e616d65223b733a373a224b6573736c6572223b733a353a22656d61696c223b733a32343a2274797265652e626c616e6461406578616d706c652e6e6574223b733a323a226964223b693a39313b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:15:"132.188.241.155";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:7:"cyril06";s:8:"lastName";s:8:"Homenick";s:5:"email";s:21:"clinton44@example.net";s:2:"id";i:91;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Kessler\", another \"ondricka.donna\""}',
                        'ip' => '146.198.147.164',
                        '_log_data' => 'a:2:{i:0;s:12:"73.119.71.96";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:14:"ondricka.donna";s:8:"lastName";s:7:"Kessler";s:5:"email";s:24:"tyree.blanda@example.net";s:2:"id";i:91;s:4:"user";R:3;}}',
                    ],
                ],
                6 => [
                    'original' => [
                        'id' => 6,
                        'log_type' => '',
                        'log_data' => '',
                        'log_data_plaintext' => '',
                        'log_message' => '',
                        'ip' => '',
                    ],
                    'processed' => [
                        'id' => 6,
                        'log_type' => '',
                        'log_data' => '',
                        'log_data_plaintext' => '',
                        'log_message' => '',
                        'ip' => '',
                    ],
                ],
            ],
        ];

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $container = self::getContainer();
        $connection = $container->get(Connection::class);

        foreach ($expected as $table => $expectedRows) {
            $result = $connection->createQueryBuilder()->select('*')->from($connection->quoteIdentifier($table))->orderBy('id', 'ASC')->executeQuery();
            while ($row = $result->fetchAssociative()) {
                foreach ($row as $column => $value) {
                    if (!is_resource($value)) {
                        continue;
                    }
                    $value = stream_get_contents($value);
                    $row[$column] = $value;
                }

                switch ($table) {
                    case 'wh_meta_data':
                        $metaData = gzdecode(hex2bin($row['meta_data']));
                        $row['_meta_data'] = $metaData;
                        break;
                    case 'wh_log':
                        if ('foo' === $row['log_type']) {
                            $metaData = base64_decode(hex2bin($row['log_data']));
                            $row['_log_data'] = $metaData;
                        } elseif ('bar' === $row['log_type']) {
                            $metaData = hex2bin($row['log_data']);
                            $row['_log_data'] = $metaData;
                        }
                        break;
                }

                $this->assertEquals($expectedRows[$row['id']]['original'], $row);
            }
        }

        $command = $application->find('pseudify:pseudonymize');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'test'], ['decorated' => false, 'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]);

        foreach ($expected as $table => $expectedRows) {
            $result = $connection->createQueryBuilder()->select('*')->from($connection->quoteIdentifier($table))->orderBy('id', 'ASC')->executeQuery();
            while ($row = $result->fetchAssociative()) {
                foreach ($row as $column => $value) {
                    if (!is_resource($value)) {
                        continue;
                    }
                    $value = stream_get_contents($value);
                    $row[$column] = $value;
                }

                switch ($table) {
                    case 'wh_meta_data':
                        $metaData = gzdecode(hex2bin($row['meta_data']));
                        $row['_meta_data'] = $metaData;
                        break;
                    case 'wh_log':
                        if ('foo' === $row['log_type']) {
                            $metaData = base64_decode(hex2bin($row['log_data']));
                            $row['_log_data'] = $metaData;
                        } elseif ('bar' === $row['log_type']) {
                            $metaData = hex2bin($row['log_data']);
                            $row['_log_data'] = $metaData;
                        }
                        break;
                }

                $this->assertEquals($expectedRows[$row['id']]['processed'], $row);
            }
        }
    }

    public function testExecuteDoNotReplacesDatabaseValues()
    {
        $expected = [
            'wh_user' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'username' => 'karl13',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$amo3Z28zNTlwZG84TG1YZg$1Ka95oewxn3xs/jLrTN0R9lhIxtNnQynBFRdE/70cAQ',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => '6bJ=yq',
                        'first_name' => 'Jordyn',
                        'last_name' => 'Shields',
                        'email' => 'madaline30@example.net',
                        'city' => 'Lake Tanner',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'username' => 'reilly.chase',
                        'password' => '$2y$04$O0XKmRw3wl9mni55dSEJXuj3vygjCgdyUviihec.PTiTAu2SS/C6u',
                        'password_hash_type' => 'bcrypt',
                        'password_plaintext' => 'wHiDoIBY<6Up',
                        'first_name' => 'Keenan',
                        'last_name' => 'King',
                        'email' => 'johns.percy@example.com',
                        'city' => 'Edwardotown',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'username' => 'hpagac',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U',
                        'password_hash_type' => 'argon2i',
                        'password_plaintext' => '[dvGd#gI',
                        'first_name' => 'Donato',
                        'last_name' => 'Keeling',
                        'email' => 'mcclure.ofelia@example.com',
                        'city' => 'North Elenamouth',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'username' => 'georgiana59',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => 'uGZIc|aX4d',
                        'first_name' => 'Maybell',
                        'last_name' => 'Anderson',
                        'email' => 'cassin.bernadette@example.net',
                        'city' => 'South Wilfordland',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'username' => 'howell.damien',
                        'password' => '$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs',
                        'password_hash_type' => 'argon2id',
                        'password_plaintext' => 'nF5;06?nsS/nE',
                        'first_name' => 'Mckayla',
                        'last_name' => 'Stoltenberg',
                        'email' => 'conn.abigale@example.net',
                        'city' => 'Dorothyfort',
                    ],
                ],
            ],
            'wh_user_session' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'session_data' => 'a:1:{s:7:"last_ip";s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";}',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'session_data' => 'a:1:{s:7:"last_ip";s:13:"107.66.23.195";}',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'session_data' => 'a:1:{s:7:"last_ip";s:13:"244.166.32.78";}',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'session_data' => 'a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'session_data' => 'a:1:{s:7:"last_ip";s:14:"197.110.248.18";}',
                    ],
                ],
            ],
            'wh_meta_data' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'meta_data' => '1f8b080000000000000365525d4f023110fc2fcd3d9a4a7bc7570951236234011340455fc8c215aea1d7d66b112e86ff6e7b7211e3db4e77ba33d32db0987d599630b4e525413d60dd80294322453dc19a3dcb3a0ced2c2f14e41c7948628632bde752e21472c115fae118b076af8b34c0ae1f1041b1d18a8ae8b34fba51deef5cb83eb9307d12bdcb347f7a4de96cf03859c6c3d9f8c5dc8f6fa2a7c9c3deb44abe9d1c1a34ff1825afb7b3974ccacddc3c9bddf0f19d8cf2e5dbfe6634b7958f33cd4506365bb8d2f093999376fa8f682408e5f8c1d551d4b0d96bb4ae949d5eaabbeab0c1d05a14d62deac46d8646ab2d9412aa6c0c4938eb12c2d0d469e9b85af262138e9a0cf11c840c35f56fbbd24a61588a0d487ecd0f901bc9b1e29587d016aeac270d74a15d56ae75e1bbc77a33346c86fed94c1c2e7864b9b542ab450aaeb297f821c048e0b64f4e85a9c3d224c1a4d5c231c5ed8e9f7f26110709f2fb1912741220711777086e605f78fef11bb387fddf33020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"139.81.0.139";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"139.81.0.139";}}',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'meta_data' => '1f8b080000000000000365525b4fc23014fe2fcd1ecda4ddc6a5842851319a800920282fe4cc55d6d0b5752dc262f8efb693458c0f4d7a6edfa5a74023fa65684cd1965518f581f67c4c28e219ea739af40ded52b433ac945030e4421c5194ab3d1322cca0e04ca29f1e0dc6ec5599f9b0e7000228374a121e7c0e702f2806dd0b3bc0177a808395c88aa76546e6b78fd3341acd270b7d3f19064fd387bd6e576c3b3db448f1318e9737f3452ec4e6453febdde87185c745faba1f8e5f4cade38c739d83c9d7b6d2ec24e6c49dfd6bd402b8b4ec601b2b7294f45bed2b696697f2ae4eb6287ae7a5b1ebc67187a2f1db162a01b5378a049c5531a6686695b04ca6acdcf85442112b800b7f27ee6ddf949421a47c03825db303145ab050b25a832f735b3548b7aa5436afde55e9aac76633c46f86fcd94ce4075c6498315cc97506b696173b10a0d8f7764e4ab96ecc92380e71bb1d4624ec741dfe1945e429f0ef6788eb19e704773b218e9290443d7fdcc8f11b4093841d36020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:15:"187.135.239.239";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:15:"187.135.239.239";}}',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'meta_data' => '1f8b08000000000000036592dd6ea33010855f65657159116ca0818922f52fca6ea5d52a4bab46bd89066c821b302c769246ddbcfbda6ca246eae5f11cfb3be31984103e34444036e240c90421759a01919c4c2444130d0990ad16bdc246102b2905b2166dbf96a8304ec97f47875aefdb9e3b99daeb1ef6eb5631e9eda634f59a697265a6f4aa9b522f7b7e7c142faff36c3edb67ac624fb37dfcbcf636f78b261ac50f7fd4dbb21885995cee65639eee76f9c3aff47bf07b4bcbb8da2c76b3d1624871c15c55a8ab953974e214e6c4e65f8c5d8d5219f16e864a601b9bbffe28fee232e2e79352f6daaccecd8e81fcc4432eea7a680c488d1755fbf4ade2a2d7ad723206221a948395596f61b152f9b9fb3b2e8c1137e21d9bae16be124302fbed853487016d4959bb35d5b7175997366b8dca663a9e67c3dc6cd897d950abb4b09856ad381a744f5ddb1c08d479c7a7c0b27385d04a1a320af1b82c20ba0e72e0110f80276109050b02887228699158ee053a7468fab926d1790b58e0533f4e7c1ad92d381eff01fdaee8ae4c020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:4;s:8:"username";s:11:"georgiana59";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:10:"uGZIc|aX4d";s:10:"first_name";s:7:"Maybell";s:9:"last_name";s:8:"Anderson";s:5:"email";s:29:"cassin.bernadette@example.net";s:4:"city";s:17:"South Wilfordland";}s:4:"key2";a:2:{s:2:"id";i:4;s:12:"session_data";s:65:"a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}";}s:4:"key3";a:1:{s:4:"key4";s:11:"20.1.58.149";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:4;s:8:"username";s:11:"georgiana59";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$SUJJeWZGSGEwS2h2TEw5Ug$kCQm4/5DqnjXc/3SiXwimtTBvbDO9H0Ru1f5hkQvE/Q";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:10:"uGZIc|aX4d";s:10:"first_name";s:7:"Maybell";s:9:"last_name";s:8:"Anderson";s:5:"email";s:29:"cassin.bernadette@example.net";s:4:"city";s:17:"South Wilfordland";}s:4:"key2";a:2:{s:2:"id";i:4;s:12:"session_data";s:65:"a:1:{s:7:"last_ip";s:37:"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8";}";}s:4:"key3";a:1:{s:4:"key4";s:11:"20.1.58.149";}}',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'meta_data' => '1f8b08000000000000036552616bc23010fd2fa11fa5336dd51a299bcc3926e8409d3abfc8d5461b4c93ac89d332fcef4b3bcb043f047277efde7b970b109ffc68121074a005463d20dd32f6086209ea31d2ea69121274d4341790516443ec1394ca13e5dc4d206354a03f8c02ad4f324fcab06b091cc8f75278ccf98e70d7c9a2b06122dc501176d63cc9de9789371f8ca6b13f9c4f16ea75d277dea76f27d52ee8617a6e7ad9d738583ecf1729e7fb95fa50c7e1688dc759fc79ea8f57baf271a3b94941a71b53287a3573d54eee808a0313869e4d3d8a18b67acdf6a3d0b307f152259b04ed58aecda69eb843d0787b808243351b411c6eaa18133433921b2a629aefcb548b209a01e3e5ddb36fbb9542b810b33d70fa44cf90294e5d412b0f659999a2661ac85c9ab4d8c9dc562ff566bc7233dedd66b08d34d59a49b149c054f6029b03824b6ce7ea94a98add52e16ec7c5b8e97a41e8e2d00adc68f8a506feff0d41dd14fa166f0f6edbde96edb8fc020ca4e19b36020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:5;s:12:"session_data";s:42:"a:1:{s:7:"last_ip";s:14:"197.110.248.18";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"83.243.216.115";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:5;s:8:"username";s:13:"howell.damien";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$ZldmOWd2TDJRb3FTNVpGNA$ORIwp6yekRx02mqM4WCTVhllgXpUpuFJZ1MmbYwAMXs";s:18:"password_hash_type";s:8:"argon2id";s:18:"password_plaintext";s:13:"nF5;06?nsS/nE";s:10:"first_name";s:7:"Mckayla";s:9:"last_name";s:11:"Stoltenberg";s:5:"email";s:24:"conn.abigale@example.net";s:4:"city";s:11:"Dorothyfort";}s:4:"key2";a:2:{s:2:"id";i:5;s:12:"session_data";s:42:"a:1:{s:7:"last_ip";s:14:"197.110.248.18";}";}s:4:"key3";a:1:{s:4:"key4";s:14:"83.243.216.115";}}',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'meta_data' => '1f8b080000000000000365915f4fc23014c5bf8aa97b240bedc6c64a480c22c428448828c10772dd0a6b5cd7ba967f1abebbedc448c25b4feeb9f7776e2fd0807e6b1a52f4c10e187580264e138a78863a9c061d4ddb146d34ab4a100c591951942b58438a7e6b0ab4dec92a7332b18d1e546b5912ee6dbb38f144b7dd305ddc505dec4de6e3f9fbf374b478dd0b56f6a6b3af6cb798783cca4b33eb3f42b0187c8adb61381835f953a5462acac27e7bbeaac63d73e88b9764e618f88cb9cc41e74b735075b298a213fbc2a70ae0a5617b730afd966d87d9f5fabe3636295af14a9be5d98a7d598291f54e14157056b49407c60a5eae9d6a51c404f0c2bd89ed13695a6c2ae6cb95b5c00ddb835005f353299cc3fe73cacda1a65af3585626bfba2b989d2d3726479de3df2d88bb05b9b805b64a33adb92c971918a88762bb37c5ce1b9fb2725523028a4818fa388afc80f871dbce3f43040e81ffcf1fa2138004894f62bf15fbd8c6381e7f00628e8a6d25020000',
                        'meta_data_plaintext' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:3;s:8:"username";s:6:"hpagac";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U";s:18:"password_hash_type";s:7:"argon2i";s:18:"password_plaintext";s:8:"[dvGd#gI";s:10:"first_name";s:6:"Donato";s:9:"last_name";s:7:"Keeling";s:5:"email";s:26:"mcclure.ofelia@example.com";s:4:"city";s:16:"North Elenamouth";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"239.27.57.12";}}',
                        '_meta_data' => 'a:3:{s:4:"key1";a:9:{s:2:"id";i:3;s:8:"username";s:6:"hpagac";s:8:"password";s:92:"$argon2i$v=19$m=8,t=1,p=1$QXNXbTRMZWxmenBRUzdwZQ$i6hntUDLa3ZFqmCG4FM0iPrpMp6d4D8XfrNBtyDmV9U";s:18:"password_hash_type";s:7:"argon2i";s:18:"password_plaintext";s:8:"[dvGd#gI";s:10:"first_name";s:6:"Donato";s:9:"last_name";s:7:"Keeling";s:5:"email";s:26:"mcclure.ofelia@example.com";s:4:"city";s:16:"North Elenamouth";}s:4:"key2";a:2:{s:2:"id";i:3;s:12:"session_data";s:41:"a:1:{s:7:"last_ip";s:13:"244.166.32.78";}";}s:4:"key3";a:1:{s:4:"key4";s:12:"239.27.57.12";}}',
                    ],
                ],
            ],
            'wh_log' => [
                1 => [
                    'original' => [
                        'id' => 1,
                        'log_type' => 'foo',
                        'log_data' => '65794a3163325679546d46745a534936496e4a76626d46735a4738784e534973496d567459576c73496a6f6962574e6a624856795a5335765a6d5673615746415a586868625842735a53356a623230694c434a7359584e30546d46745a534936496b746c5a577870626d63694c434a7063434936496a457a4d6a45364e54646d597a6f304e6a42694f6d51305a4441365a44677a5a6a706a4d6a41774f6a52694f6d5978597a676966513d3d',
                        'log_data_plaintext' => '{"userName":"ronaldo15","email":"mcclure.ofelia@example.com","lastName":"Keeling","ip":"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8"}',
                        'log_message' => '{"message":"foo text \"ronaldo15\", another \"mcclure.ofelia@example.com\""}',
                        'ip' => '1321:57fc:460b:d4d0:d83f:c200:4b:f1c8',
                        '_log_data' => '{"userName":"ronaldo15","email":"mcclure.ofelia@example.com","lastName":"Keeling","ip":"1321:57fc:460b:d4d0:d83f:c200:4b:f1c8"}',
                    ],
                ],
                2 => [
                    'original' => [
                        'id' => 2,
                        'log_type' => 'foo',
                        'log_data' => '65794a3163325679546d46745a534936496e4e3059584a724c6d70315a4751694c434a6c6257467062434936496e4e796233646c514756345957317762475575626d5630496977696247467a64453568625755694f694a4362336c6c63694973496d6c77496a6f694d5455314c6a49784e5334324e7934784f54456966513d3d',
                        'log_data_plaintext' => '{"userName":"stark.judd","email":"srowe@example.net","lastName":"Boyer","ip":"155.215.67.191"}',
                        'log_message' => '{"message":"foo text \"stark.judd\", another \"srowe@example.net\""}',
                        'ip' => '155.215.67.191',
                        '_log_data' => '{"userName":"stark.judd","email":"srowe@example.net","lastName":"Boyer","ip":"155.215.67.191"}',
                    ],
                ],
                3 => [
                    'original' => [
                        'id' => 3,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a33383a223466623a313434373a646566623a396434373a613265303a613336613a313064333a66643938223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a31323a226672656964612e6d616e7465223b733a383a226c6173744e616d65223b733a353a2254726f6d70223b733a353a22656d61696c223b733a32333a226c61666179657474653634406578616d706c652e6e6574223b733a323a226964223b693a31303b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:12:"freida.mante";s:8:"lastName";s:5:"Tromp";s:5:"email";s:23:"lafayette64@example.net";s:2:"id";i:10;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Tromp\", another \"freida.mante\""}',
                        'ip' => '4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98',
                        '_log_data' => 'a:2:{i:0;s:38:"4fb:1447:defb:9d47:a2e0:a36a:10d3:fd98";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:12:"freida.mante";s:8:"lastName";s:5:"Tromp";s:5:"email";s:23:"lafayette64@example.net";s:2:"id";i:10;s:4:"user";R:3;}}',
                    ],
                ],
                4 => [
                    'original' => [
                        'id' => 4,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31343a223234332e3230322e3234312e3637223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a31313a2267656f726769616e613539223b733a383a226c6173744e616d65223b733a353a22426c6f636b223b733a353a22656d61696c223b733a31393a226e6f6c616e3131406578616d706c652e6e6574223b733a323a226964223b693a323b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:14:"243.202.241.67";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:11:"georgiana59";s:8:"lastName";s:5:"Block";s:5:"email";s:19:"nolan11@example.net";s:2:"id";i:2;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Block\", another \"georgiana59\""}',
                        'ip' => '243.202.241.67',
                        '_log_data' => 'a:2:{i:0;s:14:"243.202.241.67";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:11:"georgiana59";s:8:"lastName";s:5:"Block";s:5:"email";s:19:"nolan11@example.net";s:2:"id";i:2;s:4:"user";R:3;}}',
                    ],
                ],
                5 => [
                    'original' => [
                        'id' => 5,
                        'log_type' => 'bar',
                        'log_data' => '613a323a7b693a303b733a31353a223133322e3138382e3234312e313535223b733a343a2275736572223b4f3a383a22737464436c617373223a353a7b733a383a22757365724e616d65223b733a373a22637972696c3036223b733a383a226c6173744e616d65223b733a383a22486f6d656e69636b223b733a353a22656d61696c223b733a32313a22636c696e746f6e3434406578616d706c652e6e6574223b733a323a226964223b693a39313b733a343a2275736572223b523a333b7d7d',
                        'log_data_plaintext' => 'a:2:{i:0;s:15:"132.188.241.155";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:7:"cyril06";s:8:"lastName";s:8:"Homenick";s:5:"email";s:21:"clinton44@example.net";s:2:"id";i:91;s:4:"user";R:3;}}',
                        'log_message' => '{"message":"bar text \"Homenick\", another \"cyril06\""}',
                        'ip' => '132.188.241.155',
                        '_log_data' => 'a:2:{i:0;s:15:"132.188.241.155";s:4:"user";O:8:"stdClass":5:{s:8:"userName";s:7:"cyril06";s:8:"lastName";s:8:"Homenick";s:5:"email";s:21:"clinton44@example.net";s:2:"id";i:91;s:4:"user";R:3;}}',
                    ],
                ],
                6 => [
                    'original' => [
                        'id' => 6,
                        'log_type' => '',
                        'log_data' => '',
                        'log_data_plaintext' => '',
                        'log_message' => '',
                        'ip' => '',
                    ],
                ],
            ],
        ];

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $container = self::getContainer();
        $connection = $container->get(Connection::class);

        foreach ($expected as $table => $expectedRows) {
            $result = $connection->createQueryBuilder()->select('*')->from($connection->quoteIdentifier($table))->orderBy('id', 'ASC')->executeQuery();
            while ($row = $result->fetchAssociative()) {
                foreach ($row as $column => $value) {
                    if (!is_resource($value)) {
                        continue;
                    }
                    $value = stream_get_contents($value);
                    $row[$column] = $value;
                }

                switch ($table) {
                    case 'wh_meta_data':
                        $metaData = gzdecode(hex2bin($row['meta_data']));
                        $row['_meta_data'] = $metaData;
                        break;
                    case 'wh_log':
                        if ('foo' === $row['log_type']) {
                            $metaData = base64_decode(hex2bin($row['log_data']));
                            $row['_log_data'] = $metaData;
                        } elseif ('bar' === $row['log_type']) {
                            $metaData = hex2bin($row['log_data']);
                            $row['_log_data'] = $metaData;
                        }
                        break;
                }

                $this->assertEquals($expectedRows[$row['id']]['original'], $row);
            }
        }

        $command = $application->find('pseudify:pseudonymize');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'nop'], ['decorated' => false, 'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]);

        foreach ($expected as $table => $expectedRows) {
            $result = $connection->createQueryBuilder()->select('*')->from($connection->quoteIdentifier($table))->orderBy('id', 'ASC')->executeQuery();
            while ($row = $result->fetchAssociative()) {
                foreach ($row as $column => $value) {
                    if (!is_resource($value)) {
                        continue;
                    }
                    $value = stream_get_contents($value);
                    $row[$column] = $value;
                }

                switch ($table) {
                    case 'wh_meta_data':
                        $metaData = gzdecode(hex2bin($row['meta_data']));
                        $row['_meta_data'] = $metaData;
                        break;
                    case 'wh_log':
                        if ('foo' === $row['log_type']) {
                            $metaData = base64_decode(hex2bin($row['log_data']));
                            $row['_log_data'] = $metaData;
                        } elseif ('bar' === $row['log_type']) {
                            $metaData = hex2bin($row['log_data']);
                            $row['_log_data'] = $metaData;
                        }
                        break;
                }

                $this->assertEquals($expectedRows[$row['id']]['original'], $row);
            }
        }
    }

    public function testExecuteDispatchesExceptions()
    {
        $this->expectException(MissingTableException::class);
        $this->expectExceptionCode(1621654991);

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);
        $command = $application->find('pseudify:pseudonymize');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'invalid'], ['decorated' => false, 'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]);
    }

    public function testExecuteThrowsExceptionOnMissingProfile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1619592554);

        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__.'/../../.test/.env');

        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);

        $container = self::getContainer();

        $command = $application->find('pseudify:pseudonymize');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['profile' => 'missing']);
    }
}
