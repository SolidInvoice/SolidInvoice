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

namespace SolidInvoice\TimeTrackingBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\TimeTrackingBundle\Entity\Timer;
use SolidInvoice\TimeTrackingBundle\Enum\TimerStatus;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<Timer>
 */
class TimerRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timer::class);
    }

    /**
     * Find the active (running or paused) timer for a user.
     */
    public function findActiveForUser(User $user): ?Timer
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->setParameter('statuses', [TimerStatus::Running, TimerStatus::Paused])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
