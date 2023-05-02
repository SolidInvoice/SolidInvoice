<?php
declare(strict_types=1);

namespace SolidInvoice\InvoiceBundle\Repository;

use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RecurringInvoiceContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecurringInvoiceContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecurringInvoiceContact[] findAll()
 * @method RecurringInvoiceContact[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<RecurringInvoiceContact>
 */
class RecurringInvoiceContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringInvoiceContact::class);
    }
}
