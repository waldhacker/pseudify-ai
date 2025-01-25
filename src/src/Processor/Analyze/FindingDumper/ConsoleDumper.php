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

namespace Waldhacker\Pseudify\Core\Processor\Analyze\FindingDumper;

use Symfony\Component\Console\Style\SymfonyStyle;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\Finding;

class ConsoleDumper implements FindingDumperInterface
{
    public function __construct(private readonly SymfonyStyle $io)
    {
    }

    /**
     * @param Finding[] $findings
     */
    #[\Override]
    public function dump(array $findings, bool $withExampleRow = false): void
    {
        $this->io->title('summary');

        $tableRows = array_map(
            static function (Finding $finding) use ($withExampleRow): array {
                $finding = $finding->toArray();
                if (!$withExampleRow) {
                    unset($finding['targetDataFrame']);
                }

                return $finding;
            },
            $findings
        );

        usort(
            $tableRows,
            /**
             * @param array{source: string, sourceData: string, target: string, targetDataFrame?: string} $itemA
             * @param array{source: string, sourceData: string, target: string, targetDataFrame?: string} $itemB
             */
            static function (array $itemA, array $itemB): int {
                $result = strcmp($itemA['source'], $itemB['source']);
                if (0 === $result) {
                    $result = strcmp($itemA['target'], $itemB['target']);
                }
                if (0 === $result) {
                    $result = strcmp($itemA['sourceData'], $itemB['sourceData']);
                }

                return $result;
            }
        );

        if (empty($tableRows)) {
            $this->io->writeln('no data found');
        } else {
            $this->io->table(
                $withExampleRow ? ['source', 'data', 'seems to be in', 'example'] : ['source', 'data', 'seems to be in'],
                $tableRows
            );
        }
    }
}
