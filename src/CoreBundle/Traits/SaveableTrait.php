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

namespace SolidInvoice\CoreBundle\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

trait SaveableTrait
{
    use DoctrineAwareTrait;

    /**
     * Persists an entity and optionally flushes the entity to the database.
     *
     * @param mixed $entity The entity to persist
     * @param bool  $flush  If an automatic flush should occur
     *
     * @throws Exception
     */
    public function save($entity, bool $flush = true): void
    {
        if (! $this->doctrine) {
            throw new Exception(sprintf('You need to call %s::setDoctrine with a valid %s instance before calling %s', static::class, ManagerRegistry::class, __METHOD__));
        }

        if (! is_object($entity)) {
            throw new Exception(sprintf('%s expects $entity yo be an object, %s given', __METHOD__, gettype($entity)));
        }

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $em->persist($entity);

        if ($flush) {
            $em->flush();
        }
    }
}
