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

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

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

    /**
     * @return array{id: Ulid, name: string, token: string, created: DateTimeInterface, updated: DateTimeInterface, lastUsed: DateTimeInterface}
     */
    public function getApiTokensForUser(UserInterface $user): array
    {
        assert($user instanceof User);

        $qb = $this->createQueryBuilder('t');

        $historyQb = $this->getEntityManager()
            ->getRepository(ApiTokenHistory::class)
            ->createQueryBuilder('th');

        $historyQb
            ->select('MAX(th.created)')
            ->where('th.token = t')
        ;

        $qb->select('t.id', 't.name', 't.token', 'h.created AS lastUsed', 'h.ip')
            ->leftJoin(
                't.history',
                'h',
                Join::WITH,
                $qb->expr()->eq('h.created', '(' . $historyQb->getDQL() . ')')
            )
            ->where('t.user = :user')
            ->groupBy('t.id', 'h.id')
            ->orderBy('t.created', 'DESC')
            ->setParameter('user', $user->getId(), UlidType::NAME);

        return $qb->getQuery()->getArrayResult();
    }

    public function revoke(ApiToken $token): void
    {
        $em = $this->getEntityManager();

        $em->remove($token);
        $em->flush();
    }
}
