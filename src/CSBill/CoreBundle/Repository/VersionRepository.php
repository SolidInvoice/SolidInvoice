<?php

namespace CSBill\CoreBundle\Repository;

use CSBill\CoreBundle\Entity\Version;
use Doctrine\ORM\EntityRepository;

class VersionRepository extends EntityRepository
{
    /**
     * Updates the current version
     *
     * @param $verion
     */
    public function updateVersion($verion)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('DELETE FROM CSBillCoreBundle:Version');

        $query->execute();

        $entity = new Version($verion);
        $entityManager->persist($entity);

        return $entityManager->flush();
    }
}
