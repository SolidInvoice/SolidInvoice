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

namespace SolidInvoice\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function getUsernameForToken(string $token): ?string
    {
        $q = $this
            ->createQueryBuilder('t')
            ->select('u.email')
            ->join('t.user', 'u')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return null;
        }
    }

    public function getApiTokensForUser(UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('t');

        $hqb = $this->getEntityManager()
            ->getRepository(ApiTokenHistory::class)
            ->createQueryBuilder('th');

        $hqb->select($qb->expr()->max('th.created'))
            ->where('th.token = t')
            ->setMaxResults(1);

        $qb->select('t', 'h')
            ->leftJoin(
                't.history',
                'h',
                Join::WITH,
                $qb->expr()->eq('h.created', '(' . $hqb->getDQL() . ')')
            )
            ->where('t.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getArrayResult();
    }
}
