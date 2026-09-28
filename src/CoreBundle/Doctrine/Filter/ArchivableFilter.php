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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use function sprintf;

class ArchivableFilter extends SQLFilter
{
    private const string ARCHIVABLE_CLASS = Archivable::class;

    public static function disableForGrid(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query
            ->beforeQuery(static fn () => $entityManager->getFilters()->suspend('archivable'))
            ->afterQuery(static fn () => $entityManager->getFilters()->restore('archivable'));

        // where() replaces the WHERE clause but not the bound-parameter collection.
        // Reset it before setting :archived, or a parameter another suspension call
        // (suspendForJoinedAssociations(), on the same query builder) already bound
        // stays bound with no placeholder left to match it, and Doctrine rejects the
        // query with "the query defines 1 parameters and you bound 2".
        $query
            ->getQueryBuilder()
            ->setParameters(new ArrayCollection())
            ->where(sprintf('%1$s.archived is not null or %1$s.archived = :archived', $query->getRootAlias()))
            ->setParameter('archived', false);

        return $query;
    }

    /**
     * Suspends the filter for the whole query, then re-applies its normal
     * (not-archived) constraint to only the query's own root entity.
     *
     * Use this where a grid joins an Archivable association (a document's
     * client, for example) only to display or sort by it: the association
     * is a historical fact about the row, not something that should hide
     * the row when it gets archived later.
     *
     * This suspends the filter for the *whole* query: any other Archivable
     * association joined into the same query loses its filtering too, not
     * only the one this method is protecting. Safe today because no grid
     * joins two Archivable associations at once — reconsider this method
     * if one ever does.
     */
    public static function suspendForJoinedAssociations(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query
            ->beforeQuery(static fn () => $entityManager->getFilters()->suspend('archivable'))
            ->afterQuery(static fn () => $entityManager->getFilters()->restore('archivable'));

        $query
            ->getQueryBuilder()
            ->andWhere(self::notArchivedCondition($query->getRootAlias()))
            ->setParameter('archivableFilterNotArchived', false);

        return $query;
    }

    private static function notArchivedCondition(string $alias): string
    {
        return sprintf('(%1$s.archived IS NULL OR %1$s.archived = :archivableFilterNotArchived)', $alias);
    }

    /**
     * @param ClassMetadata<object> $targetEntity
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (! in_array(self::ARCHIVABLE_CLASS, $targetEntity->reflClass->getTraitNames(), true)) {
            return '';
        }

        $value = $this->getConnection()->getDatabasePlatform()->convertBooleans(false);

        return sprintf('(%1$s.archived IS NULL OR %1$s.archived = %2$s)', $targetTableAlias, $value);
    }
}
