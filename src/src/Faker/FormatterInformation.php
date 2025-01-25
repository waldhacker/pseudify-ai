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

namespace Waldhacker\Pseudify\Core\Faker;

use Faker\Generator;
use Faker\Provider\Base;
use Faker\UniqueGenerator;
use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 */
class FormatterInformation
{
    public function __construct(
        private readonly string $locale,
        private readonly Generator $generator,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @return array<string, array{name: string, parameters: array<string, array{name: string, description: string|null, types: array<int, string>, hasDefaultValue: bool, defaultValue: mixed, defaultValueType: string|null, isOptional: bool}>, example: string|null}>
     */
    public function buildFormatterInformation(): array
    {
        return $this->cache->get('faker_formatter_information_'.md5($this->locale), function (ItemInterface $item): array {
            $item->tag(['faker_formatter_information']);

            $docBlockFactory = DocBlockFactory::createInstance();
            $phpDocTypeHelper = new PhpDocTypeHelper();

            $providers = $this->generator->getProviders();
            $providers[] = new Base($this->generator);

            $methods = [
                'calculateRoutingNumberChecksum',
                'getDefaultTimezone',
                'getFormatConstants',
                'getFormats',
                'image',
                'file',
                'optional',
                'unique',
                'valid',
                'passthrough',
                'randomElements',
                'randomKey',
                'setDefaultTimezone',
                'shuffle',
                'shuffleArray',
                'toLower',
                'toUpper',
            ];

            $formatters = [];
            foreach ($providers as $provider) {
                $providerClass = $provider::class;
                $providerReflection = new \ReflectionObject($provider);

                foreach ($providerReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
                    if (Base::class === $methodReflection->getDeclaringClass()->getName() && Base::class !== $providerClass) {
                        continue;
                    }
                    $methodName = $methodReflection->name;

                    if ($methodReflection->isConstructor()) {
                        continue;
                    }

                    if (in_array($methodName, $methods)) {
                        continue;
                    }
                    $methods[] = $methodName;

                    $docBlockTypes = [];
                    $docBlockDescriptions = [];
                    if ($docComment = $methodReflection->getDocComment()) {
                        $docBlock = $docBlockFactory->create($docComment);
                        foreach ($docBlock->getTagsByName('param') as $tag) {
                            if (method_exists($tag, 'getType') && method_exists($tag, 'getVariableName') && null !== $tag->getType()) {
                                $docBlockTypes[$tag->getVariableName()] = array_map(fn (Type $type): string => $type->getBuiltinType(), $phpDocTypeHelper->getTypes($tag->getType()));
                            }
                            if (method_exists($tag, 'getDescription') && method_exists($tag, 'getVariableName') && null !== $tag->getDescription()) {
                                $docBlockDescriptions[$tag->getVariableName()] = $tag->getDescription()->render();
                            }
                        }
                    }

                    $parameters = [];
                    foreach ($methodReflection->getParameters() as $parameterReflection) {
                        $parameterName = $parameterReflection->getName();
                        $reflectionTypes = array_unique(array_merge(
                            $docBlockTypes[$parameterName] ?? [],
                            $this->normalizeParameterTypeReflection($parameterReflection)
                        ));

                        $parameters[$parameterName] = [
                            'name' => $parameterName,
                            'description' => $docBlockDescriptions[$parameterName] ?? null,
                            'types' => $reflectionTypes,
                            'hasDefaultValue' => $parameterReflection->isDefaultValueAvailable(),
                            'defaultValue' => $parameterReflection->isDefaultValueAvailable() ? $parameterReflection->getDefaultValue() : null,
                            'defaultValueType' => $parameterReflection->isDefaultValueAvailable() ? get_debug_type($parameterReflection->getDefaultValue()) : null,
                            'isOptional' => $parameterReflection->isOptional(),
                        ];
                    }

                    try {
                        $example = $this->generator->format($methodName);
                    } catch (\InvalidArgumentException|\ArgumentCountError) {
                        $example = null;
                    }

                    if (is_array($example)) {
                        $example = "array('".implode("', '", $example)."')";
                    } elseif (is_string($example)) {
                        $example = $example;
                    } elseif ($example instanceof \DateTime) {
                        $example = "DateTime('".$example->format('Y-m-d H:i:s')."')";
                    } elseif ($example instanceof Generator || $example instanceof UniqueGenerator) {
                        $example = null;
                    } else {
                        $example = json_encode($example);
                    }

                    $formatters[$methodName] = [
                        'name' => $methodName,
                        'parameters' => $parameters,
                        'example' => $example,
                    ];
                }
            }

            ksort($formatters);

            return $formatters;
        });
    }

    /**
     * @return array<int, string>
     */
    private function normalizeParameterTypeReflection(\ReflectionParameter $parameterReflection): array
    {
        $typeReflection = $parameterReflection->getType();
        if (!$typeReflection) {
            return [];
        }

        if (!($typeReflection instanceof \ReflectionNamedType || $typeReflection instanceof \ReflectionUnionType)) {
            return [];
        }

        return $typeReflection instanceof \ReflectionUnionType
            ? array_map(fn (\ReflectionNamedType $type): string => $type->getName(), array_filter($typeReflection->getTypes(), fn (\ReflectionType $type): bool => $type instanceof \ReflectionNamedType))
            : [$typeReflection->getName()]
        ;
    }
}
