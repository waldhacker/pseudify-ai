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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Writer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Yaml\Yaml;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionSerializerFactory;

/**
 * @internal
 */
class ProfileDefinitionWriter
{
    public function __construct(
        private readonly ProfileDefinitionSerializerFactory $profileDefinitionSerializerFactory,
        private readonly Filesystem $filesystem,
        private readonly string $dataDirectory,
    ) {
    }

    public function write(string $filename, ProfileDefinition $profileDefinition): void
    {
        $path = $profileDefinition->getPath();
        $path = !empty($path) && $this->filesystem->exists($path)
            ? $path
            : sprintf('%s/%s.yaml', $this->dataDirectory, preg_replace('/[^a-z0-9]+/', '-', strtolower($filename)))
        ;

        $yamlProfileDefinition = $this->profileDefinitionSerializerFactory
            ->create()
            ->serialize(
                $profileDefinition,
                'yaml',
                [
                    AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                    AbstractNormalizer::GROUPS => ['userland'],
                    YamlEncoder::YAML_INLINE => 999,
                    YamlEncoder::YAML_FLAGS => Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE,
                ]
            )
        ;

        $this->filesystem->dumpFile($path, $yamlProfileDefinition);
    }
}
