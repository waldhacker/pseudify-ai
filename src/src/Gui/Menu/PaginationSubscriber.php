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

namespace Waldhacker\Pseudify\Core\Gui\Menu;

use Knp\Component\Pager\Event\BeforeEvent;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;

/**
 * @internal
 */
class PaginationSubscriber extends \Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber
{
    private bool $isLoaded = false;

    public function __construct(private readonly ConnectionManager $connectionManager)
    {
    }

    #[\Override]
    public function before(BeforeEvent $event): void
    {
        if ($this->isLoaded) {
            return;
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $event->getEventDispatcher();

        $connection = $this->connectionManager->getConnection();
        $dispatcher->addSubscriber(new DBALQueryBuilderSubscriber($connection));

        $this->isLoaded = true;
    }
}
