<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Traits;

use Symfony\Bridge\Doctrine\RegistryInterface;

trait SaveableTrait
{
    use DoctrineAwareTrait;

    /**
     * Persists an entity and optionally flushes the entity to the database.
     *
     * @param mixed $entity The entity to persist
     * @param bool  $flush  If an automatic flush should occur
     *
     * @throws \Exception
     */
    public function save($entity, bool $flush = true)
    {
        if (!$this->doctrine) {
            throw new \Exception(sprintf('You need to call %s::setDoctrine with a valid %s instance before calling %s', get_class($this), RegistryInterface::class, __METHOD__));
        }

        if (!is_object($entity)) {
            throw new \Exception(sprintf('%s expects $entity yo be an object, %s given', __METHOD__, gettype($entity)));
        }

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager();

        $em->persist($entity);
        $flush && $em->flush();
    }
}
