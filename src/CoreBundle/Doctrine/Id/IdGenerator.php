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

namespace SolidInvoice\CoreBundle\Doctrine\Id;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use function get_class;

final class IdGenerator extends AbstractIdGenerator
{
    /**
     * @var array<string, int>
     */
    private array $entityIds = [];

    public function generate(EntityManagerInterface $em, $entity)
    {
        assert(is_object($entity));

        if (isset($this->entityIds[get_class($entity)])) {
            return ++$this->entityIds[get_class($entity)];
        }

        $classMetadata = $em->getClassMetadata(get_class($entity));
        $idColumn = $classMetadata->getSingleIdentifierFieldName();

        $em->getFilters()->disable('archivable');

        try {
            $maxId = $em->getRepository(get_class($entity))
                ->createQueryBuilder('e')
                ->select('MAX(e.' . $idColumn . ')')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $maxId = 0;
        }

        $em->getFilters()->enable('archivable');

        return $this->entityIds[get_class($entity)] = ($maxId ? ++$maxId : 1);
    }
}
