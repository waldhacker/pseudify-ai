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

namespace Waldhacker\Pseudify\Core\Processor\Analyze;

use Symfony\Component\String\Exception\InvalidArgumentException;
use Symfony\Component\String\UnicodeString;

class StringHelper
{
    /**
     * @param array<array-key, mixed> $input
     *
     * @return UnicodeString[]
     */
    public static function normalizeStrings(array $input): array
    {
        $result = [];
        /** @var mixed $data */
        foreach ($input as $data) {
            $result[] = self::normalizeString($data);
        }

        return $result;
    }

    public static function normalizeString(mixed $input): UnicodeString
    {
        if (is_string($input)) {
            try {
                $result = new UnicodeString($input);
            } catch (InvalidArgumentException) {
                $normalized = preg_replace('/[^[:print:]]/', '', $input);
                $result = new UnicodeString($normalized ?? $input);
            }
        } else {
            $result = new UnicodeString(var_export($input, true));
        }

        return $result;
    }

    public static function pregUnquote(string $string): string
    {
        return strtr($string, [
            '\\.' => '.',
            '\\\\' => '\\',
            '\\+' => '+',
            '\\*' => '*',
            '\\?' => '?',
            '\\[' => '[',
            '\\^' => '^',
            '\\]' => ']',
            '\\$' => '$',
            '\\(' => '(',
            '\\)' => ')',
            '\\{' => '{',
            '\\}' => '}',
            '\\=' => '=',
            '\\!' => '!',
            '\\<' => '<',
            '\\>' => '>',
            '\\|' => '|',
            '\\:' => ':',
            '\\-' => '-',
        ]);
    }
}
