<?php

declare(strict_types=1);

/**
 * Parallel claim race worker for UniqueValueClaimStoreTest.
 *
 * Usage: php claim_race_worker.php <claimDirectory> <domainHash> <candidate>
 * Prints acquired=1 on success, acquired=0 when the claim is already taken.
 */

$autoloadCandidates = [
    dirname(__DIR__, 3).'/vendor/autoload.php',
    dirname(__DIR__, 2).'/vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

$autoload = null;
foreach ($autoloadCandidates as $candidatePath) {
    if (is_file($candidatePath)) {
        $autoload = $candidatePath;
        break;
    }
}

if (null === $autoload) {
    fwrite(STDERR, "autoload not found\n");
    exit(2);
}

require $autoload;

use Waldhacker\Pseudify\Core\Faker\UniqueValueClaimStore;

if ($argc < 4) {
    fwrite(STDERR, "usage: claim_race_worker.php <claimDirectory> <domainHash> <candidate>\n");
    exit(2);
}

$claimDirectory = $argv[1];
$domainHash = $argv[2];
$candidate = $argv[3];

$store = new UniqueValueClaimStore($claimDirectory);
$acquired = $store->claim($domainHash, $candidate);

echo 'acquired='.($acquired ? '1' : '0')."\n";

exit($acquired ? 0 : 1);
