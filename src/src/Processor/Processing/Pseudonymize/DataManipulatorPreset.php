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

namespace Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Waldhacker\Pseudify\Core\Faker\Faker;
use Waldhacker\Pseudify\Core\Processor\Processing\GenericDataProcessing;
use Waldhacker\Pseudify\Core\Processor\Processing\GenericDataProcessingInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\Helper;

class DataManipulatorPreset
{
    /**
     * @param array<int, mixed> $fakerArguments
     * @param string[]          $conditions
     *
     * @api
     */
    public static function scalarData(
        string $fakerFormatter,
        ?string $processingIdentifier = null,
        ?string $scope = null,
        ?array $fakerArguments = [],
        ?string $writeToPath = null,
        ?array $conditions = null,
    ): GenericDataProcessingInterface {
        return new GenericDataProcessing(
            static function (DataManipulatorContext $context) use ($writeToPath, $scope, $fakerFormatter, $fakerArguments): void {
                $processedData = $context->getProcessedData();
                $dataToFake = $processedData;

                $writeToPath = Helper::buildPropertyAccessorPath($processedData, $writeToPath);
                if (!empty($writeToPath)) {
                    $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
                }

                if (!empty($writeToPath) && (is_array($processedData) || is_object($processedData))) {
                    $dataToFake = $propertyAccessor->getValue($processedData, $writeToPath);
                }

                $scopedFaker = $context->fake(
                    scope: $scope ?? Faker::DEFAULT_SCOPE,
                    source: $dataToFake
                );
                /** @var callable $callable */
                $callable = [$scopedFaker, $fakerFormatter];
                $fakedData = call_user_func($callable, ...($fakerArguments ?? []));

                if (!empty($writeToPath) && (is_array($processedData) || is_object($processedData))) {
                    $propertyAccessor->setValue($processedData, $writeToPath, $fakedData);
                    $fakedData = $processedData;
                }

                $context->setProcessedData($fakedData);
            },
            $processingIdentifier,
            $conditions,
            [
                GenericDataProcessingInterface::CONTEXT_TYPE => $fakerFormatter,
                GenericDataProcessingInterface::CONTEXT_CONTEXT => $fakerArguments,
                GenericDataProcessingInterface::CONTEXT_SCOPE => $scope,
                GenericDataProcessingInterface::CONTEXT_PATH => $writeToPath,
            ]
        );
    }

    /**
     * @param string[] $conditions
     *
     * @api
     */
    public static function ip(
        ?string $processingIdentifier = null,
        ?string $scope = null,
        ?string $writeToPath = null,
        ?array $conditions = null,
    ): GenericDataProcessingInterface {
        return new GenericDataProcessing(
            static function (DataManipulatorContext $context) use ($writeToPath, $scope): void {
                $processedData = $context->getProcessedData();
                $dataToFake = $processedData;

                $writeToPath = Helper::buildPropertyAccessorPath($processedData, $writeToPath);
                if (!empty($writeToPath)) {
                    $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
                }

                if (!empty($writeToPath) && (is_array($processedData) || is_object($processedData))) {
                    $dataToFake = $propertyAccessor->getValue($processedData, $writeToPath);
                }

                if (!is_string($dataToFake)) {
                    return;
                }

                $fakedData = null;
                if (false !== filter_var($dataToFake, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $fakedData = $context->fake(
                        source: $dataToFake,
                        scope: $scope ?? Faker::DEFAULT_SCOPE
                    )->ipv6();
                } elseif (false !== filter_var($dataToFake, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $fakedData = $context->fake(
                        source: $dataToFake,
                        scope: $scope ?? Faker::DEFAULT_SCOPE
                    )->ipv4();
                }

                if (null === $fakedData) {
                    return;
                }

                if (!empty($writeToPath) && (is_array($processedData) || is_object($processedData))) {
                    $propertyAccessor->setValue($processedData, $writeToPath, $fakedData);
                    $fakedData = $processedData;
                }

                $context->setProcessedData($fakedData);
            },
            $processingIdentifier,
            $conditions,
            [
                GenericDataProcessingInterface::CONTEXT_SCOPE => $scope,
                GenericDataProcessingInterface::CONTEXT_PATH => $writeToPath,
            ]
        );
    }
}
