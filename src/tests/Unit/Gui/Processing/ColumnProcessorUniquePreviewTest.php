<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Gui\Processing;

use PHPUnit\Framework\TestCase;
use Waldhacker\Pseudify\Core\Faker\Faker;
use Waldhacker\Pseudify\Core\Gui\Processing\ColumnProcessor;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;

class ColumnProcessorUniquePreviewTest extends TestCase
{
    /**
     * Contract: GUI preview must call withUnique(true) when Meaning.property.unique is true.
     */
    public function testPreviewCallsWithUniqueTrueForUniqueMeaning(): void
    {
        $fakerMock = $this->createPartialMock(Faker::class, ['withScope', 'withSource', 'withUnique', '__call']);
        $fakerMock->expects($this->once())->method('withScope')->with(Faker::DEFAULT_SCOPE)->willReturnSelf();
        $fakerMock->expects($this->once())->method('withSource')->with('alice@example.com')->willReturnSelf();
        $fakerMock->expects($this->once())->method('withUnique')->with(true)->willReturnSelf();
        $fakerMock->expects($this->once())->method('__call')->with('safeEmail', [])->willReturn('fake@example.com');

        $conditionExpressionProvider = $this->createMock(ConditionExpressionProvider::class);
        $conditionExpressionProvider->method('getFunctions')->willReturn([]);

        $column = new Column(
            identifier: 'email',
            meanings: [
                new Meaning(
                    identifier: 'm-email',
                    property: new Property(
                        path: null,
                        scope: null,
                        type: 'safeEmail',
                        minimumGraphemeLength: 3,
                        context: [],
                        unique: true,
                    ),
                    name: 'Email',
                ),
            ],
        );

        $processor = new ColumnProcessor($conditionExpressionProvider, $fakerMock);
        $result = $processor->processDatabaseRow($column, ['email' => 'alice@example.com']);

        $this->assertSame('fake@example.com', $result['meanings'][0]['fakedValue']);
    }

    /**
     * Contract: GUI preview must call withUnique(false) when Meaning.property.unique is absent/false
     * (guards against hard-wired withUnique(true)).
     */
    public function testPreviewCallsWithUniqueFalseForNonUniqueMeaning(): void
    {
        $fakerMock = $this->createPartialMock(Faker::class, ['withScope', 'withSource', 'withUnique', '__call']);
        $fakerMock->expects($this->once())->method('withScope')->with(Faker::DEFAULT_SCOPE)->willReturnSelf();
        $fakerMock->expects($this->once())->method('withSource')->with('alice@example.com')->willReturnSelf();
        $fakerMock->expects($this->once())->method('withUnique')->with(false)->willReturnSelf();
        $fakerMock->expects($this->once())->method('__call')->with('safeEmail', [])->willReturn('fake@example.com');

        $conditionExpressionProvider = $this->createMock(ConditionExpressionProvider::class);
        $conditionExpressionProvider->method('getFunctions')->willReturn([]);

        $column = new Column(
            identifier: 'email',
            meanings: [
                new Meaning(
                    identifier: 'm-email',
                    property: new Property(
                        path: null,
                        scope: null,
                        type: 'safeEmail',
                        minimumGraphemeLength: 3,
                        context: [],
                        unique: null,
                    ),
                    name: 'Email',
                ),
            ],
        );

        $processor = new ColumnProcessor($conditionExpressionProvider, $fakerMock);
        $result = $processor->processDatabaseRow($column, ['email' => 'alice@example.com']);

        $this->assertSame('fake@example.com', $result['meanings'][0]['fakedValue']);
    }
}
