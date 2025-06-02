<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\SaasBundle\Entity\SaasTrial;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<SaasTrial>
 */
final class TrialRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaasTrial::class);
    }

    public function userHasTrial(User $user): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->setParameter('user', $user->getId(), UlidType::NAME);

        try {
            return $qb->getQuery()->getSingleScalarResult() > 0;
        } catch (NoResultException) {
            return false;
        } catch (NonUniqueResultException) {
            // If there are multiple results, we still return true as the user has a trial
            return true;
        }
    }

    public function createTrial(User $user, Subscription $subscription): SaasTrial
    {
        $trial = new SaasTrial();
        $trial->setUser($user);
        $trial->setSubscription($subscription);

        $this->_em->persist($trial);
        $this->_em->flush();

        return $trial;
    }
}
