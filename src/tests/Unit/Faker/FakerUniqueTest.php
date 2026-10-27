<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Faker;

use Faker\Generator;
use Faker\Provider\Base;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Waldhacker\Pseudify\Core\Faker\Faker;
use Waldhacker\Pseudify\Core\Faker\UniqueValueClaimStore;

class FakerUniqueTest extends TestCase
{
    private string $claimDirectory;

    protected function setUp(): void
    {
        $this->claimDirectory = sys_get_temp_dir().'/pseudify_faker_unique_'.bin2hex(random_bytes(8));
        mkdir($this->claimDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->claimDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->claimDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $fileInfo) {
            $path = $fileInfo->getPathname();
            $fileInfo->isDir() ? rmdir($path) : unlink($path);
        }
        rmdir($this->claimDirectory);
    }

    public function testSameSourceYieldsSameOutputWithUnique(): void
    {
        $faker = $this->createFaker(
            emailSequence: ['alice@example.com'],
            source: 'original-a@example.com',
        )->withUnique(true);

        $first = $faker->email();
        $second = $faker->email();

        $this->assertSame($first, $second);
        $this->assertSame('alice@example.com', $first);
    }

    public function testDifferentSourcesWithConstantGeneratorBecomeDistinctWithUnique(): void
    {
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $generator = $this->createGeneratorWithEmailSequence(['dup@example.com']);

        $base = new Faker(
            faker: $generator,
            cache: $cache,
            claimStore: $claimStore,
        );

        $first = $base->withSource('source-1')->withUnique(true)->email();
        $second = $base->withSource('source-2')->withUnique(true)->email();

        $this->assertNotSame($first, $second);
        $this->assertContains('dup@example.com', [$first, $second]);
        $fallback = 'dup@example.com' === $first ? $second : $first;
        $this->assertMatchesRegularExpression('/^u[0-9a-f]{16}@example\.com$/', $fallback);
    }

    public function testExhaustionFallsBackToEmailFormatterShape(): void
    {
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $domainHash = hash('sha256', json_encode(['*', 'email', []], JSON_THROW_ON_ERROR));
        $this->assertTrue($claimStore->claim($domainHash, 'dup@example.com'));

        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $generator = $this->createGeneratorWithEmailSequence(['dup@example.com']);

        $faker = (new Faker(
            faker: $generator,
            cache: $cache,
            claimStore: $claimStore,
            source: 'exhausted-source@example.com',
        ))->withUnique(true);

        $value = $faker->email();

        $this->assertNotSame('dup@example.com', $value);
        $this->assertMatchesRegularExpression('/^u[0-9a-f]{16}@example\.com$/', $value);
    }

    public function testPreclaimedCandidateTriggersRetry(): void
    {
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $domainHash = hash('sha256', json_encode(['*', 'email', []], JSON_THROW_ON_ERROR));
        $this->assertTrue($claimStore->claim($domainHash, 'first@example.com'));

        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $generator = $this->createGeneratorWithEmailSequence([
            'first@example.com',
            'second@example.com',
        ]);

        $faker = (new Faker(
            faker: $generator,
            cache: $cache,
            claimStore: $claimStore,
            source: 'retry-source@example.com',
        ))->withUnique(true);

        $this->assertSame('second@example.com', $faker->email());
    }

    public function testExhaustionFallsBackToStringFormatterShape(): void
    {
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $domainHash = hash('sha256', json_encode(['*', 'userName', []], JSON_THROW_ON_ERROR));
        $this->assertTrue($claimStore->claim($domainHash, 'dupuser'));

        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $generator = new Generator();
        $generator->addProvider(new class($generator) extends Base {
            public function userName(): string
            {
                return 'dupuser';
            }
        });

        $source = 'original-user';
        $faker = (new Faker(
            faker: $generator,
            cache: $cache,
            claimStore: $claimStore,
            source: $source,
        ))->withUnique(true);

        $value = $faker->userName();
        $sourceHash = hash('sha256', bin2hex(serialize($source)));
        $hex12 = substr($sourceHash, 0, 12);

        $this->assertNotSame('dupuser', $value);
        $this->assertSame('dupuser_u'.$hex12, $value);
    }

    /**
     * Contract: withUnique(false) after withSource must keep the same cache key as the
     * plain path. Sequence oracle: on cache miss the second generator value appears.
     */
    public function testWithUniqueFalseKeepsCacheKeyCompatibleWithPlainPath(): void
    {
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $generator = $this->createGeneratorWithEmailSequence([
            'first@example.com',
            'second@example.com',
        ]);

        $base = new Faker(
            faker: $generator,
            cache: $cache,
            claimStore: $claimStore,
        );

        $source = 'original@example.com';
        $plain = $base->withSource($source)->email();
        $withUniqueFalse = $base->withSource($source)->withUnique(false)->email();

        $this->assertSame($plain, $withUniqueFalse);
        $this->assertSame('first@example.com', $plain);
    }

    /**
     * @param list<string> $emailSequence
     */
    private function createFaker(array $emailSequence, mixed $source = null): Faker
    {
        return new Faker(
            faker: $this->createGeneratorWithEmailSequence($emailSequence),
            cache: new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()),
            claimStore: new UniqueValueClaimStore($this->claimDirectory),
            source: $source,
        );
    }

    /**
     * @param list<string> $emailSequence
     */
    private function createGeneratorWithEmailSequence(array $emailSequence): Generator
    {
        $generator = new Generator();
        $generator->addProvider(new class($generator, $emailSequence) extends Base {
            /** @param list<string> $sequence */
            public function __construct(Generator $generator, private array $sequence, private int $index = 0)
            {
                parent::__construct($generator);
            }

            public function email(): string
            {
                if ([] === $this->sequence) {
                    return 'dup@example.com';
                }

                if (!isset($this->sequence[$this->index])) {
                    return $this->sequence[array_key_last($this->sequence)];
                }

                return $this->sequence[$this->index++];
            }
        });

        return $generator;
    }
}
