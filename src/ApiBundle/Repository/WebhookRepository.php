<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ApiBundle\Entity\Webhook;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<Webhook>
 */
final class WebhookRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Webhook::class);
    }

    /**
     * @return list<Webhook>
     */
    public function findActiveByEvent(string $event): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.active = :active')
            ->andWhere('JSON_CONTAINS(w.events, :event) = 1')
            ->setParameter('active', true)
            ->setParameter('event', json_encode($event))
            ->getQuery()
            ->getResult();
    }
}
