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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 *
 * Based on Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\DBALQueryBuilderSubscriber
 */
class DBALQueryBuilderSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function items(ItemsEvent $event): void
    {
        if ($event->target instanceof QueryBuilder) {
            $target = $event->target;

            $qb = $this
                ->connection
                ->createQueryBuilder()
                ->select('COUNT(*)')
                ->from('('.(clone $target)->resetOrderBy()->getSQL().')', 'tmp')
                ->setParameters($target->getParameters(), $target->getParameterTypes())
            ;

            $compat = $qb->executeQuery();

            $event->count = method_exists($compat, 'fetchColumn') ? (int) $compat->fetchColumn(0) : (int) $compat->fetchOne();

            $event->items = [];
            if ($event->count) {
                $qb = clone $target;
                $qb
                    ->setFirstResult($event->getOffset())
                    ->setMaxResults($event->getLimit())
                ;

                $event->items = $qb
                    ->executeQuery()
                    ->fetchAllAssociative()
                ;
            }

            $event->stopPropagation();
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 10 /* make sure to transform before any further modifications */],
        ];
    }
}
