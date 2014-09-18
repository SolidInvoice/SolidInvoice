<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Repository;

use CSBill\CoreBundle\Entity\Version;
use Doctrine\ORM\EntityRepository;

class VersionRepository extends EntityRepository
{
    /**
     * Updates the current version
     *
     * @param $version
     */
    public function updateVersion($version)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('DELETE FROM CSBillCoreBundle:Version');

        $query->execute();

        $entity = new Version($version);
        $entityManager->persist($entity);

        $entityManager->flush();
    }
}
