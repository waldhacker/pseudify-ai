<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Waldhacker\Pseudify\Core\Command\PseudonymizeCommand;
use Waldhacker\Pseudify\Core\Faker\PseudonymizeRuntimeCachePurger;

class PseudonymizeCommandPurgeOnSignalTest extends TestCase
{
    public function testPurgeOnSignalInvokesPurgerExactlyOnce(): void
    {
        $purger = $this->createMock(PseudonymizeRuntimeCachePurger::class);
        $purger->expects($this->once())->method('purge');

        $reflection = new \ReflectionClass(PseudonymizeCommand::class);
        $command = $reflection->newInstanceWithoutConstructor();

        $property = $reflection->getProperty('runtimeCachePurger');
        $property->setAccessible(true);
        $property->setValue($command, $purger);

        $method = $reflection->getMethod('purgeOnSignal');
        $method->setAccessible(true);
        $method->invoke($command);
    }
}
