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
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
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
     * Deletes the invitations matching the given ids. Each invitation is fetched
     * with a DQL query (rather than find()) so that the company filter is applied
     * and only invitations belonging to the current company are removed —
     * find() would return identity-map hits without applying the filter. Returns
     * the number of invitations that were deleted.
     *
     * @param array<string> $ids
     */
    public function deleteInvitations(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            $invitation = $this->createQueryBuilder('u')
                ->where('u.id = :id')
                ->setParameter('id', $id, UlidType::NAME)
                ->getQuery()
                ->getOneOrNullResult();

            if ($invitation instanceof UserInvitation) {
                $this->_em->remove($invitation);
                ++$deleted;
            }
        }

        if ($deleted > 0) {
            $this->_em->flush();
        }

        return $deleted;
    }

    public function delete(UserInvitation $invitation): void
    {
        $this->_em->remove($invitation);
        $this->_em->flush();
    }

    /**
     * Flags pending invitations whose validity window has elapsed as expired.
     * Returns the number of invitations that were updated.
     */
    public function markExpired(DateTimeInterface $now): int
    {
        return (int) $this->createQueryBuilder('u')
            ->update()
            ->set('u.status', ':expired')
            ->where('u.status = :pending')
            ->andWhere('u.expiresAt IS NOT NULL')
            ->andWhere('u.expiresAt < :now')
            ->setParameter('expired', InvitationStatus::Expired)
            ->setParameter('pending', InvitationStatus::Pending)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    /**
     * Returns pending invitations that are due an expiry reminder: still pending,
     * not yet reminded, and expiring within the given window (between now and the
     * threshold).
     *
     * @return list<UserInvitation>
     */
    public function findDueForExpiryReminder(DateTimeInterface $now, DateTimeInterface $threshold): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.reminderSentAt IS NULL')
            ->andWhere('u.expiresAt IS NOT NULL')
            ->andWhere('u.expiresAt > :now')
            ->andWhere('u.expiresAt <= :threshold')
            ->setParameter('status', InvitationStatus::Pending)
            ->setParameter('now', $now)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function countPendingInvitations(): int
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->setParameter('status', InvitationStatus::Pending);

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
            ->setParameter('status', InvitationStatus::Pending)
            ->setParameter('companyId', $company->getId(), UlidType::NAME);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }
}
