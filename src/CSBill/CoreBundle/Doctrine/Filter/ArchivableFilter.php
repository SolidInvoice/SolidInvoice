<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;

class ArchivableFilter extends SQLFilter
{
    const ARCHIVABLEE_CLASS = 'CSBill\CoreBundle\Traits\Entity\Archivable';

    /**
     * {@inheritdoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!in_array(self::ARCHIVABLEE_CLASS, $targetEntity->reflClass->getTraitNames(), true)) {
            return '';
        }

        return $targetTableAlias.'.archived IS NULL';
    }
}
