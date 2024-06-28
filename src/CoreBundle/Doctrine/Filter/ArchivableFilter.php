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

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\CoreBundle\Traits\Entity\Archivable;

class ArchivableFilter extends SQLFilter
{
    private const ARCHIVABLE_CLASS = Archivable::class;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (! in_array(self::ARCHIVABLE_CLASS, $targetEntity->reflClass->getTraitNames(), true)) {
            return '';
        }

        return sprintf('(%1$s.archived IS NULL OR %1$s.archived = 0)', $targetTableAlias);
    }
}
