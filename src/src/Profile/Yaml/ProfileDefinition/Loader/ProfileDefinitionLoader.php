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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionSerializerFactory;

/**
 * @internal
 */
class ProfileDefinitionLoader extends FileLoader
{
    public function __construct(
        private readonly ProfileDefinitionSerializerFactory $profileDefinitionSerializerFactory,
        private readonly TagAwareCacheInterface $cache,
        FileLocatorInterface $locator,
        ?string $env = null,
    ) {
        parent::__construct($locator, $env);
    }

    #[\Override]
    public function load(mixed $resource, ?string $type = null): ProfileDefinition
    {
        $path = $this->locator->locate($resource);

        if (!stream_is_local($path)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $path));
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" not found.', $path));
        }

        $contentHash = sha1_file($path);

        $cacheKey = sprintf('profile_definition_%s_%s', hash('md5', $path), $contentHash);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($path, $contentHash): ProfileDefinition {
            $item->tag(['profile_definition']);

            return $this->profileDefinitionSerializerFactory
                ->create()
                ->deserialize(
                    file_get_contents($path),
                    ProfileDefinition::class,
                    'yaml',
                    [
                        AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
                        AbstractNormalizer::GROUPS => ['userland'],
                    ]
                )
                ->setPath($path)
                ->setContentHash($contentHash)
            ;
        });
    }

    #[\Override]
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return \is_string($resource) && \in_array(pathinfo($resource, \PATHINFO_EXTENSION), ['yml', 'yaml'], true) && (!$type || 'yaml' === $type);
    }
}
