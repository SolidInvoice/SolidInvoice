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
use SolidInvoice\CoreBundle\Entity\AmbiguousDiscountUnit;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<AmbiguousDiscountUnit>
 */
final class AmbiguousDiscountUnitRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AmbiguousDiscountUnit::class);
    }

    /**
     * Every captured row for the currently-active company (CompanyFilter applies),
     * oldest capture first. The table is small and read whole by one audit report and
     * one console command, so there is no pagination here.
     *
     * @return list<AmbiguousDiscountUnit>
     */
    public function findAllOrderedByCapturedAt(): array
    {
        /** @var list<AmbiguousDiscountUnit> $result */
        $result = $this->createQueryBuilder('u')
            ->orderBy('u.capturedAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
