<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Profile\Yaml\Profile;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\GenericDataProcessingInterface;
use Waldhacker\Pseudify\Core\Profile\Analyze\TableDefinitionAutoConfiguration as AnalyzeTableDefinitionAutoConfiguration;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\TableDefinitionAutoConfiguration as PseudonymizeTableDefinitionAutoConfiguration;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\PseudonymizeProfileFactory;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Schema;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Table;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionFactory;

class PseudonymizeProfileFactoryUniqueTest extends TestCase
{
    /**
     * Contract: Property unique:true must reach DataProcessing CONTEXT_UNIQUE via
     * PseudonymizeProfileFactory → scalarData(unique: …).
     */
    public function testUniquePropertyReachesDataProcessingContext(): void
    {
        $profileDefinition = new ProfileDefinition(
            identifier: 'unique-profile',
            description: 'unique passthrough',
            schema: new Schema([
                new Table('users', [
                    new Column('email', meanings: [
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
                    ]),
                ]),
            ]),
        );

        $profileDefinitionFactory = $this->createMock(ProfileDefinitionFactory::class);
        $profileDefinitionFactory->expects($this->once())
            ->method('load')
            ->with('unique-profile', null)
            ->willReturn($profileDefinition);

        $pseudonymizeAutoConfiguration = $this->createMock(PseudonymizeTableDefinitionAutoConfiguration::class);
        $pseudonymizeAutoConfiguration->expects($this->once())
            ->method('configure')
            ->willReturnArgument(0);

        $factory = new PseudonymizeProfileFactory(
            profileDefinitionCollection: $this->createMock(ProfileDefinitionCollection::class),
            profileDefinitionFactory: $profileDefinitionFactory,
            analyzeTableDefinitionAutoConfiguration: $this->createMock(AnalyzeTableDefinitionAutoConfiguration::class),
            pseudonymizeTableDefinitionAutoConfiguration: $pseudonymizeAutoConfiguration,
            cache: $this->createMock(TagAwareCacheInterface::class),
        );

        $profile = $factory->createProfile('unique-profile');
        $processings = $profile->getTableDefinition()->getTable('users')->getColumn('email')->getDataProcessings();

        $this->assertCount(1, $processings);
        $processing = $processings[0];
        $this->assertInstanceOf(GenericDataProcessingInterface::class, $processing);
        $context = $processing->getContext();
        $this->assertIsArray($context);
        $this->assertArrayHasKey(GenericDataProcessingInterface::CONTEXT_UNIQUE, $context);
        $this->assertTrue($context[GenericDataProcessingInterface::CONTEXT_UNIQUE]);
    }

    /**
     * Contract: Property unique missing/false must not set CONTEXT_UNIQUE to true
     * (guards against hard-wired unique: true in the factory).
     */
    public function testNonUniquePropertyReachesDataProcessingContextAsFalse(): void
    {
        $profileDefinition = new ProfileDefinition(
            identifier: 'non-unique-profile',
            description: 'non-unique passthrough',
            schema: new Schema([
                new Table('users', [
                    new Column('email', meanings: [
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
                    ]),
                ]),
            ]),
        );

        $profileDefinitionFactory = $this->createMock(ProfileDefinitionFactory::class);
        $profileDefinitionFactory->expects($this->once())
            ->method('load')
            ->with('non-unique-profile', null)
            ->willReturn($profileDefinition);

        $pseudonymizeAutoConfiguration = $this->createMock(PseudonymizeTableDefinitionAutoConfiguration::class);
        $pseudonymizeAutoConfiguration->expects($this->once())
            ->method('configure')
            ->willReturnArgument(0);

        $factory = new PseudonymizeProfileFactory(
            profileDefinitionCollection: $this->createMock(ProfileDefinitionCollection::class),
            profileDefinitionFactory: $profileDefinitionFactory,
            analyzeTableDefinitionAutoConfiguration: $this->createMock(AnalyzeTableDefinitionAutoConfiguration::class),
            pseudonymizeTableDefinitionAutoConfiguration: $pseudonymizeAutoConfiguration,
            cache: $this->createMock(TagAwareCacheInterface::class),
        );

        $profile = $factory->createProfile('non-unique-profile');
        $processings = $profile->getTableDefinition()->getTable('users')->getColumn('email')->getDataProcessings();

        $this->assertCount(1, $processings);
        $context = $processings[0]->getContext();
        $this->assertIsArray($context);
        $this->assertArrayHasKey(GenericDataProcessingInterface::CONTEXT_UNIQUE, $context);
        $this->assertFalse($context[GenericDataProcessingInterface::CONTEXT_UNIQUE]);
    }
}
