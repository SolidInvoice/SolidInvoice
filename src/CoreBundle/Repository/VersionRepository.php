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

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Version;

class VersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Version::class);
    }

    /**
     * Updates the current version.
     */
    public function updateVersion(string $version): void
    {
        $entityManager = $this->getEntityManager();

        $qb = $this->createQueryBuilder('v');
        $qb->delete()
            ->getQuery()
            ->execute();

        $entity = new Version($version);

        try {
            $entityManager->persist($entity);

            $entityManager->flush();
        } catch (ORMException) {
            // noop
        }
    }

    public function getCurrentVersion(): string
    {
        $qb = $this->createQueryBuilder('v');

        $qb->select('v.version')
            ->setMaxResults(1);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return '0.0.0';
        }
    }
}
