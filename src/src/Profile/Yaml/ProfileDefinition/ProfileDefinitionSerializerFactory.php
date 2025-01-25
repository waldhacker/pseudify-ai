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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Loader\Serializer\Mapping\YamlFilesLoader;

/**
 * @internal
 */
class ProfileDefinitionSerializerFactory
{
    /**
     * @param array<array-key, string> $classMetaDataDefinitionFiles
     */
    public function __construct(private readonly array $classMetaDataDefinitionFiles)
    {
    }

    public function create(): Serializer
    {
        return new Serializer(
            [
                new ObjectNormalizer(
                    classMetadataFactory: new ClassMetadataFactory(
                        new AttributeLoader()
                    ),
                    propertyTypeExtractor: new ReflectionExtractor(),
                    classDiscriminatorResolver: new ClassDiscriminatorFromClassMetadata(
                        new ClassMetadataFactory(
                            new YamlFilesLoader($this->classMetaDataDefinitionFiles)
                        )
                    )
                ),
                new ArrayDenormalizer(),
            ],
            [
                new YamlEncoder(),
            ]
        );
    }
}
