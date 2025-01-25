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

namespace Waldhacker\Pseudify\Core\Processor\Processing;

class Helper
{
    public static function buildPropertyAccessorPath(mixed $heystack, ?string $propertyPath = null): ?string
    {
        if (null === $propertyPath || '' === $propertyPath) {
            return null;
        }

        if (!is_array($heystack) && !is_object($heystack)) {
            return null;
        }

        if (is_array($heystack) || $heystack instanceof \ArrayAccess) {
            return implode('', array_map(fn (string $item): string => sprintf('[%s]', $item), explode('.', $propertyPath)));
        }

        preg_match_all('#\[([^\]]+)\]#', $propertyPath, $matches);
        if (!$matches[1]) {
            return $propertyPath;
        }

        return implode('.', $matches[1]);
    }
}
