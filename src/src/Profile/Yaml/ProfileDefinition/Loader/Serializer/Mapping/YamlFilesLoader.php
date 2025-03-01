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
 *
 * This file is "multi file" version of `Symfony\Component\Serializer\Mapping\Loader\YamlFilesLoader`
 */

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Loader\Serializer\Mapping;

use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Serializer\Exception\MappingException;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\FileLoader;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class YamlFilesLoader extends FileLoader
{
    private ?Parser $yamlParser = null;
    /** @var array<string, array<array-key, mixed>>|null */
    private ?array $classes = null;

    /**
     * @param array<array-key, string> $files
     */
    public function __construct(private readonly array $files)
    {
        foreach ($files as $file) {
            if (!is_file($file)) {
                throw new MappingException(sprintf('The mapping file "%s" does not exist.', $file));
            }

            if (!is_readable($file)) {
                throw new MappingException(sprintf('The mapping file "%s" is not readable.', $file));
            }
        }

        $this->file = implode(', ', $files);
    }

    #[\Override]
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (!$this->classes ??= $this->getClassesFromYaml()) {
            return false;
        }

        if (!isset($this->classes[$classMetadata->getName()])) {
            return false;
        }

        $yaml = $this->classes[$classMetadata->getName()];

        if (isset($yaml['attributes']) && \is_array($yaml['attributes'])) {
            $attributesMetadata = $classMetadata->getAttributesMetadata();

            foreach ($yaml['attributes'] as $attribute => $data) {
                if (isset($attributesMetadata[$attribute])) {
                    $attributeMetadata = $attributesMetadata[$attribute];
                } else {
                    $attributeMetadata = new AttributeMetadata($attribute);
                    $classMetadata->addAttributeMetadata($attributeMetadata);
                }

                if (isset($data['groups'])) {
                    if (!\is_array($data['groups'])) {
                        throw new MappingException(sprintf('The "groups" key must be an array of strings in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                    }

                    foreach ($data['groups'] as $group) {
                        if (!\is_string($group)) {
                            throw new MappingException(sprintf('Group names must be strings in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                        }

                        $attributeMetadata->addGroup($group);
                    }
                }

                if (isset($data['max_depth'])) {
                    if (!\is_int($data['max_depth'])) {
                        throw new MappingException(sprintf('The "max_depth" value must be an integer in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                    }

                    $attributeMetadata->setMaxDepth($data['max_depth']);
                }

                if (isset($data['serialized_name'])) {
                    if (!\is_string($data['serialized_name']) || '' === $data['serialized_name']) {
                        throw new MappingException(sprintf('The "serialized_name" value must be a non-empty string in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                    }

                    $attributeMetadata->setSerializedName($data['serialized_name']);
                }

                if (isset($data['serialized_path'])) {
                    try {
                        $attributeMetadata->setSerializedPath(new PropertyPath((string) $data['serialized_path']));
                    } catch (InvalidPropertyPathException) {
                        throw new MappingException(sprintf('The "serialized_path" value must be a valid property path in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                    }
                }

                if (isset($data['ignore'])) {
                    if (!\is_bool($data['ignore'])) {
                        throw new MappingException(sprintf('The "ignore" value must be a boolean in "%s" for the attribute "%s" of the class "%s".', $this->file, $attribute, $classMetadata->getName()));
                    }

                    $attributeMetadata->setIgnore($data['ignore']);
                }

                foreach ($data['contexts'] ?? [] as $line) {
                    $groups = $line['groups'] ?? [];

                    if ($context = $line['context'] ?? false) {
                        $attributeMetadata->setNormalizationContextForGroups($context, $groups);
                        $attributeMetadata->setDenormalizationContextForGroups($context, $groups);
                    }

                    if ($context = $line['normalization_context'] ?? false) {
                        $attributeMetadata->setNormalizationContextForGroups($context, $groups);
                    }

                    if ($context = $line['denormalization_context'] ?? false) {
                        $attributeMetadata->setDenormalizationContextForGroups($context, $groups);
                    }
                }
            }
        }

        if (isset($yaml['discriminator_map'])) {
            if (!isset($yaml['discriminator_map']['type_property'])) {
                throw new MappingException(sprintf('The "type_property" key must be set for the discriminator map of the class "%s" in "%s".', $classMetadata->getName(), $this->file));
            }

            if (!isset($yaml['discriminator_map']['mapping'])) {
                throw new MappingException(sprintf('The "mapping" key must be set for the discriminator map of the class "%s" in "%s".', $classMetadata->getName(), $this->file));
            }

            $classMetadata->setClassDiscriminatorMapping(new ClassDiscriminatorMapping(
                $yaml['discriminator_map']['type_property'],
                $yaml['discriminator_map']['mapping']
            ));
        }

        return true;
    }

    /**
     * Return the names of the classes mapped in this file.
     *
     * @return string[]
     */
    public function getMappedClasses(): array
    {
        return array_keys($this->classes ??= $this->getClassesFromYaml());
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    private function getClassesFromYaml(): array
    {
        $this->yamlParser ??= new Parser();

        $classes = [];
        foreach ($this->files as $file) {
            if (!stream_is_local($file)) {
                throw new MappingException(sprintf('This is not a local file "%s".', $file));
            }

            $classes = array_replace_recursive(
                $classes,
                $this->yamlParser->parseFile($file, Yaml::PARSE_CONSTANT)
            );
        }

        if (empty($classes)) {
            return [];
        }

        return $classes;
    }
}
