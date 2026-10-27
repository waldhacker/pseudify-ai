<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Gui\Form\ProfileDefinition\Column\Dto;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Faker\FormatterInformation;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto\ColumnConfigurationDto;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto\ColumnConfigurationDtoFactory;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;

class ColumnConfigurationDtoFactoryUniqueTest extends TestCase
{
    use ProphecyTrait;

    public function testMeaningToArrayRoundtripIncludesUniqueTrue(): void
    {
        $factory = $this->createFactory();
        $property = new Property(
            path: null,
            scope: null,
            type: 'safeEmail',
            minimumGraphemeLength: 3,
            context: [],
            unique: true,
        );
        $meaning = new Meaning('meaning-1', $property, 'Email');

        $method = new \ReflectionMethod(ColumnConfigurationDtoFactory::class, 'meaningToArray');
        $method->setAccessible(true);
        /** @var array{property: array<string, mixed>} $array */
        $array = $method->invoke($factory, $meaning);

        $this->assertTrue($array['property']['unique']);

        $dto = new ColumnConfigurationDto([
            [
                'identifier' => 'encoding-1',
                'name' => 'Encoding',
                'conditions' => [],
                'encoders' => [
                    [
                        'identifier' => 'encoder-1',
                        'encoder' => 'scalar',
                        'context' => [[]],
                    ],
                ],
                'meanings' => [
                    [
                        'identifier' => 'meaning-1',
                        'name' => 'Email',
                        'conditions' => [],
                        'property' => [
                            'path' => null,
                            'scope' => null,
                            'type' => 'safeEmail',
                            'minimumGraphemeLength' => 3,
                            'unique' => true,
                            'context' => [[]],
                        ],
                    ],
                ],
            ],
        ]);

        $meanings = $factory->dtoToMeanings($dto);
        $this->assertCount(1, $meanings);
        $this->assertTrue($meanings[0]->getProperty()->isUnique());
    }

    public function testMissingUniqueInDtoYieldsIsUniqueFalse(): void
    {
        $factory = $this->createFactory();

        $dto = new ColumnConfigurationDto([
            [
                'identifier' => 'encoding-1',
                'name' => 'Encoding',
                'conditions' => [],
                'encoders' => [
                    [
                        'identifier' => 'encoder-1',
                        'encoder' => 'scalar',
                        'context' => [[]],
                    ],
                ],
                'meanings' => [
                    [
                        'identifier' => 'meaning-missing-unique',
                        'name' => 'Email',
                        'conditions' => [],
                        'property' => [
                            'path' => null,
                            'scope' => null,
                            'type' => 'safeEmail',
                            'minimumGraphemeLength' => 3,
                            'context' => [[]],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertArrayNotHasKey('unique', $dto->encodings[0]['meanings'][0]['property']);

        $meanings = $factory->dtoToMeanings($dto);
        $this->assertCount(1, $meanings);
        $this->assertFalse($meanings[0]->getProperty()->isUnique());
    }

    private function createFactory(): ColumnConfigurationDtoFactory
    {
        $encoderCollection = $this->prophesize(AdvancedEncoderCollection::class);
        $formatterInformation = $this->prophesize(FormatterInformation::class);
        $formatterInformation->buildFormatterInformation()->willReturn([
            'safeEmail' => [
                'name' => 'safeEmail',
                'parameters' => [],
                'example' => 'user@example.com',
            ],
        ]);
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(\Prophecy\Argument::cetera())->willReturn('Auto generated');

        $encoderCollection->hasEncoder(\Prophecy\Argument::any())->willReturn(false);
        $encoderCollection->getEncoder(\Prophecy\Argument::any())->willReturn(new \Waldhacker\Pseudify\Core\Processor\Encoder\ScalarEncoder());
        $encoderCollection->getEncoderIdentifier(\Prophecy\Argument::any())->willReturn('scalar');

        return new ColumnConfigurationDtoFactory(
            $encoderCollection->reveal(),
            $formatterInformation->reveal(),
            $translator->reveal(),
        );
    }
}
