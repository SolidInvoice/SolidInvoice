<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Generator\BillingIdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use function assert;
use function get_class;

final class AutoIncrementIdGenerator implements IdGeneratorInterface
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function generate(object $entity, string $field): string
    {
        $em = $this->registry->getManagerForClass(get_class($entity));
        assert($em instanceof EntityManager);

        $filters = $em->getFilters();
        assert($filters instanceof FilterCollection);

        $filters->disable('archivable');

        try {
            $lastId = $this->registry
                ->getRepository(get_class($entity))
                ->createQueryBuilder('e')
                ->select('MAX(e.' . $field . ')')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException|NoResultException $e) {
            $lastId = 0;
        } finally {
            $filters->enable('archivable');
        }

        return (string) ($lastId + 1);
    }
}
