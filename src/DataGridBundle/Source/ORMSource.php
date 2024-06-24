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

namespace SolidInvoice\DataGridBundle\Source;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\DataGridBundle\Grid;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\Source\ORMSourceTest
 */
class ORMSource implements SourceInterface
{
    final public const ALIAS = 'd';

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function fetch(Grid $grid): QueryBuilder
    {
        $em = $this->registry->getManagerForClass($grid->entityFQCN());

        assert($em instanceof EntityManagerInterface);

        return $em
            ->getRepository($grid->entityFQCN())
            ->createQueryBuilder(self::ALIAS);
    }
}
