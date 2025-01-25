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

namespace Waldhacker\Pseudify\Core\Processor\Processing\Analyze;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\String\Exception\InvalidArgumentException;
use Symfony\Component\String\UnicodeString;
use Waldhacker\Pseudify\Core\Processor\Processing\GenericDataProcessing;
use Waldhacker\Pseudify\Core\Processor\Processing\GenericDataProcessingInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\Helper;

class SourceDataCollectorPreset
{
    /**
     * @param string[]|null $conditions
     *
     * @api
     */
    public static function scalarData(
        ?string $processingIdentifier = null,
        ?int $minimumGraphemeLength = null,
        ?string $collectFromPath = null,
        ?array $conditions = null,
    ): GenericDataProcessingInterface {
        return new GenericDataProcessing(
            static function (SourceDataCollectorContext $context) use ($collectFromPath, $minimumGraphemeLength): void {
                $decodedData = $context->getDecodedData();

                if (null !== $collectFromPath && '' !== $collectFromPath && (is_array($decodedData) || is_object($decodedData))) {
                    $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
                    $decodedData = $propertyAccessor->getValue($decodedData, Helper::buildPropertyAccessorPath($decodedData, $collectFromPath));
                }

                if (is_string($decodedData)) {
                    $graphemeLength = self::normalizeString($decodedData)->length();
                    if ($graphemeLength < ($minimumGraphemeLength ?? 3)) {
                        return;
                    }
                }

                $context->addCollectedData($decodedData);
            },
            $processingIdentifier,
            $conditions,
            [
                GenericDataProcessingInterface::CONTEXT_PATH => $collectFromPath,
                GenericDataProcessingInterface::CONTEXT_MINIMUM_GRAPHEME_LENGTH => $minimumGraphemeLength,
            ]
        );
    }

    private static function normalizeString(string $input): UnicodeString
    {
        try {
            $result = new UnicodeString($input);
        } catch (InvalidArgumentException) {
            $normalized = preg_replace('/[^[:print:]]/', '', $input);
            $result = new UnicodeString($normalized ?? $input);
        }

        return $result;
    }
}
