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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<ExportJob>
 */
final class ExportJobRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportJob::class);
    }

    /**
     * Returns all export jobs for the currently-active company (CompanyFilter applies),
     * requested by the given user, most recent first.
     *
     * @return list<ExportJob>
     */
    public function findForUser(Ulid $userId): array
    {
        /** @var list<ExportJob> $result */
        $result = $this->createQueryBuilder('j')
            ->andWhere('j.requestedBy = :userId')
            ->setParameter('userId', $userId, UlidType::NAME)
            ->orderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
