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

use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Loader\Serializer\Mapping\YamlFilesLoader;

/**
 * @internal
 */
class AdvancedEncoderCollection
{
    /** @var array<string, EncoderInterface> */
    private array $encoders = [];

    /**
     * @param array<array-key, string> $classMetaDataDefinitionFiles
     */
    public function __construct(
        EncoderCollection $encoderCollection,
        array $classMetaDataDefinitionFiles,
    ) {
        $classDiscriminatorResolver = new ClassDiscriminatorFromClassMetadata(
            new ClassMetadataFactory(
                new YamlFilesLoader($classMetaDataDefinitionFiles)
            )
        );

        foreach ($encoderCollection->getEncoders() as $encoder) {
            $identifier = $classDiscriminatorResolver->getTypeForMappedObject($encoder);
            if (!$identifier) {
                continue;
            }

            $this->encoders[$identifier] = $encoder;
        }
    }

    public function hasEncoder(string $identifier): bool
    {
        return isset($this->encoders[$identifier]);
    }

    public function getEncoder(string $identifier): ?EncoderInterface
    {
        return $this->encoders[$identifier] ?? null;
    }

    /**
     * @return array<string, EncoderInterface>
     */
    public function getEncoders(): array
    {
        return $this->encoders;
    }

    /**
     * @return array<string, EncoderInterface>
     */
    public function getEncodersIndexedByShortName(): array
    {
        $encoders = [];
        foreach ($this->encoders as $encoder) {
            $encoders[$this->getEncoderShortName($encoder)] = $encoder;
        }
        ksort($encoders);

        return $encoders;
    }

    public function getEncoderIdentifier(EncoderInterface $encoder): ?string
    {
        return array_keys(array_filter($this->encoders, fn (EncoderInterface $collectionEncoder): bool => $collectionEncoder::class === $encoder::class))[0] ?? null;
    }

    public function getEncoderByClassName(string $encoderClassName): ?EncoderInterface
    {
        return array_values(array_filter($this->encoders, fn (EncoderInterface $collectionEncoder): bool => $collectionEncoder::class === $encoderClassName))[0] ?? null;
    }

    public function getEncoderShortName(EncoderInterface $encoder): string
    {
        $shortName = (new \ReflectionClass($encoder))->getShortName();
        $convenientShortName = preg_replace('/(.*)Encoder$/', '$1', $shortName);

        return $convenientShortName ?? $shortName;
    }

    /**
     * @return array<int, string>
     */
    public function getEncoderIdentifiers(): array
    {
        return array_keys($this->encoders);
    }
}
