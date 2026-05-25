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

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @see \SolidInvoice\UserBundle\Tests\Repository\UserRepositoryTest
 *
 * @extends \SolidWorx\Platform\PlatformBundle\Repository\UserRepository<User>
 */
class UserRepository extends \SolidWorx\Platform\PlatformBundle\Repository\UserRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getUserCount(): int
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u.id)');

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }

    /**
     * Counts users associated with the given company. Used by the `team_seats`
     * quota gate. Scoped explicitly via the user-companies join (not the global
     * `CompanyFilter`, since `User` participates as the *inverse* side of the
     * Many-to-Many on `Company::users`).
     */
    public function getUserCountForCompany(Company $company): int
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u.id)')
            ->innerJoin('u.companies', 'c')
            ->where('c.id = :companyId')
            ->setParameter('companyId', $company->getId(), UlidType::NAME);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id', 'u.email', 'u.mobile', 'u.enabled', 'u.created', 'u.lastLogin')
            ->groupBy('u.id');

        return $qb;
    }

    public function getRecentlyJoinedCount(int $days = 30): int
    {
        $qb = $this->createQueryBuilder('u');
        $date = new DateTimeImmutable(sprintf('-%d days', $days));

        $qb->select('COUNT(u.id)')
            ->where('u.created >= :date')
            ->setParameter('date', $date);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }
}
