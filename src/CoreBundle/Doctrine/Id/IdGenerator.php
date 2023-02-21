<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Doctrine\Id;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Model\Status;

final class IdGenerator extends AbstractIdGenerator
{
    public function generate(EntityManagerInterface $em, $entity)
    {
        assert(is_object($entity));

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

        return ($maxId ? ++$maxId : 1);
    }
}
