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
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<UserInvitation>
 */
final class UserInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserInvitation::class);
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id', 'u.status', 'u.email', 'u.created')
            ->groupBy('u.id');

        return $qb;
    }

    /**
     * @param array<string> $ids
     * @throws ConversionException|Exception
     */
    public function deleteInvitations(array $ids): int
    {
        $platform = $this->_em->getConnection()->getDatabasePlatform();
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
        $this->_em->remove($invitation);
        $this->_em->flush();
    }
}
