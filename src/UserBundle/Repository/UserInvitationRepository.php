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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<UserInvitation>
 * @see \SolidInvoice\UserBundle\Tests\Repository\UserInvitationRepositoryTest
 */
final class UserInvitationRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserInvitation::class);
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id', 'u.status', 'u.email', 'u.created', 'inviter.email as inviterEmail')
            ->leftJoin('u.invitedBy', 'inviter')
            ->groupBy('u.id');

        return $qb;
    }

    /**
     * @param array<string> $ids
     * @throws ConversionException|Exception
     */
    public function deleteInvitations(array $ids): int
    {
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $type = Type::getType(UlidType::NAME);
        $convertId = static fn (string $id) => $type->convertToDatabaseValue($id, $platform);

        return $this->createQueryBuilder('u')
            ->delete()
            ->where('u.id IN (:ids)')
            ->setParameter('ids', array_map($convertId, $ids))
            ->getQuery()
            ->execute();
    }

    public function delete(UserInvitation $invitation): void
    {
        $this->getEntityManager()->remove($invitation);
        $this->getEntityManager()->flush();
    }

    public function countPendingInvitations(): int
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->setParameter('status', UserInvitation::STATUS_PENDING);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }

    /**
     * Counts pending invitations for the given company. Used by the
     * `team_seats` quota gate to combine with the existing user count
     * (a sent-but-not-yet-accepted invitation reserves a seat).
     */
    public function countPending(Company $company): int
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->andWhere('u.company = :companyId')
            ->setParameter('status', UserInvitation::STATUS_PENDING)
            ->setParameter('companyId', $company->getId(), UlidType::NAME);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }
}
