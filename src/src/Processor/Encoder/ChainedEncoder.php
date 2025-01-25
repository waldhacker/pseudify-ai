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

namespace Waldhacker\Pseudify\Core\Processor\Encoder;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\Helper;

class ChainedEncoder extends AbstractEncoder implements EncoderInterface
{
    /** @var array<int, EncoderInterface> */
    protected array $encoders = [];
    /** @var array<string, mixed> */
    protected array $defaultContext = [];
    /** @var array<array-key, array{data: mixed, dataPickerPath: string}> */
    protected array $dataPickerStack = [];
    protected PropertyAccessorInterface $propertyAccessor;

    /**
     * @param array<array-key, mixed> $encoders
     *
     * @api
     */
    public function __construct(array $encoders = [])
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();

        foreach ($encoders as $encoder) {
            if (!$encoder instanceof EncoderInterface) {
                continue;
            }
            $this->encoders[] = $encoder;
        }
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        foreach ($this->encoders as $index => $encoder) {
            $dataPickerPath = Helper::buildPropertyAccessorPath($data, $encoder->getContext()[self::DATA_PICKER_PATH] ?? null);
            array_push($this->dataPickerStack, ['data' => $data, 'dataPickerPath' => $dataPickerPath]);
            if (!empty($dataPickerPath)) {
                $data = $this->propertyAccessor->getValue($data, $dataPickerPath);
            }

            $data = $encoder->decode($data, $context);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        foreach (array_reverse($this->encoders) as $encoder) {
            $data = $encoder->encode($data, $context);

            $dataPickerStackItem = array_pop($this->dataPickerStack);
            $dataPickerPath = $dataPickerStackItem['dataPickerPath'] ?? null;
            $dataPickerData = $dataPickerStackItem['data'] ?? null;
            if ($dataPickerPath && (is_array($dataPickerData) || $dataPickerData instanceof \ArrayAccess || is_object($dataPickerData))) {
                $this->propertyAccessor->setValue($dataPickerData, $dataPickerPath, $data);
                $data = $dataPickerData;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function canDecode(mixed $data, array $context = []): bool
    {
        return true;
    }

    /**
     * @api
     */
    #[\Override]
    public function decodesToScalarDataOnly(): bool
    {
        return false;
    }

    /**
     * @api
     */
    public function hasEncoder(int $index): bool
    {
        return isset($this->encoders[$index]);
    }

    /**
     * @api
     */
    public function getEncoder(int $index): EncoderInterface
    {
        if (!$this->hasEncoder($index)) {
            throw new MissingEncoderException(sprintf('missing encoder "%s"', $index), 1_621_656_967);
        }

        return $this->encoders[$index];
    }

    /**
     * @return array<int, EncoderInterface>
     *
     * @api
     */
    public function getEncoders(): array
    {
        return $this->encoders;
    }
}
