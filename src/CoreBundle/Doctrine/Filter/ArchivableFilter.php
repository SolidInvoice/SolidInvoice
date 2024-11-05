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

namespace SolidInvoice\CoreBundle\Doctrine\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use function sprintf;

class ArchivableFilter extends SQLFilter
{
    private const ARCHIVABLE_CLASS = Archivable::class;

    public static function disableForGrid(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query
            ->beforeQuery(static fn () => $entityManager->getFilters()->suspend('archivable'))
            ->afterQuery(static fn () => $entityManager->getFilters()->restore('archivable'));

        $query
            ->getQueryBuilder()
            ->where(sprintf('%1$s.archived is not null or %1$s.archived = :archived', $query->getRootAlias()))
            ->setParameter('archived', false);

        return $query;
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (! in_array(self::ARCHIVABLE_CLASS, $targetEntity->reflClass->getTraitNames(), true)) {
            return '';
        }

        return sprintf('(%1$s.archived IS NULL OR %1$s.archived = 0)', $targetTableAlias);
    }
}
