<?php

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
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\ResetPasswordRequest;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter('user', $resetPasswordRequest->getUser()->getId(), UlidType::NAME)
            ->getQuery()
            ->execute()
        ;
    }

    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        // Normally there is only 1 max request per use, but written to be flexible
        /** @var ResetPasswordRequestInterface $resetPasswordRequest */
        $resetPasswordRequest = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->orderBy('t.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null !== $resetPasswordRequest && ! $resetPasswordRequest->isExpired()) {
            return $resetPasswordRequest->getRequestedAt();
        }

        return null;
    }

    public function removeRequests(object $user): void
    {
        $query = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter('user', $user->getId(), UlidType::NAME)
        ;

        $query->getQuery()->execute();
    }

    /**
     * @param User $user
     */
    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        return new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);
    }
}
