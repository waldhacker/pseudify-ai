<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;

class PropertyUniqueTest extends TestCase
{
    public function testMissingUniqueMeansIsUniqueFalse(): void
    {
        $property = new Property(
            path: null,
            scope: null,
            type: 'safeEmail',
            minimumGraphemeLength: 3,
            context: [],
        );

        $this->assertFalse($property->isUnique());
    }

    public function testNullUniqueMeansIsUniqueFalse(): void
    {
        $property = new Property(
            path: null,
            scope: null,
            type: 'safeEmail',
            minimumGraphemeLength: 3,
            context: [],
            unique: null,
        );

        $this->assertFalse($property->isUnique());
    }

    public function testUniqueTrueMeansIsUniqueTrue(): void
    {
        $property = new Property(
            path: null,
            scope: null,
            type: 'safeEmail',
            minimumGraphemeLength: 3,
            context: [],
            unique: true,
        );

        $this->assertTrue($property->isUnique());
    }

    public function testMergeCopiesUniqueFlag(): void
    {
        $base = new Property(null, null, 'safeEmail', 3, [], unique: false);
        $incoming = new Property('email', 'users', 'safeEmail', 3, [], unique: true);

        $merged = $base->merge($incoming);

        $this->assertTrue($merged->isUnique());
        $this->assertSame('email', $merged->getPath());
    }

    /**
     * Contract: YAML/userland payload with unique:true denormalizes onto Property
     * (Groups prototype/userland), so profile YAML round-trips the flag.
     */
    public function testUserlandDenormalizeReadsUniqueTrue(): void
    {
        $serializer = new Serializer([
            new ObjectNormalizer(
                classMetadataFactory: new ClassMetadataFactory(new AttributeLoader()),
                nameConverter: new CamelCaseToSnakeCaseNameConverter(),
            ),
        ]);

        /** @var Property $property */
        $property = $serializer->denormalize(
            [
                'path' => null,
                'scope' => null,
                'type' => 'safeEmail',
                'minimum_grapheme_length' => 3,
                'context' => [],
                'unique' => true,
            ],
            Property::class,
            context: ['groups' => ['userland']],
        );

        $this->assertTrue($property->isUnique());
        $this->assertTrue($property->getUnique());
    }
}
