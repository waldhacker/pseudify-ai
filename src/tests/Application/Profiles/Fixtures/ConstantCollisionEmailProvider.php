<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Application\Profiles\Fixtures;

use Faker\Provider\Base;
use Waldhacker\Pseudify\Core\Faker\FakeDataProviderInterface;

class ConstantCollisionEmailProvider extends Base implements FakeDataProviderInterface
{
    public function constantCollisionEmail(): string
    {
        return 'dup@example.com';
    }
}
