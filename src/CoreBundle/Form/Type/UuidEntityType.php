<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Form\Type;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\CoreBundle\Doctrine\Loader\ORMUuidQueryBuilderLoader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UuidEntityType extends EntityType
{
    /**
     * @throws Exception
     */
    public function getLoader(ObjectManager $manager, object $queryBuilder, string $class): ORMUuidQueryBuilderLoader
    {
        assert($manager instanceof EntityManagerInterface);
        assert($queryBuilder instanceof QueryBuilder);

        return new ORMUuidQueryBuilderLoader($queryBuilder, $manager->getConnection()->getDatabasePlatform());
    }
}
