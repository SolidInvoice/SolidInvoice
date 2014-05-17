<?php

namespace CSBill\PaymentBundle\Repository;

use CSBill\InvoiceBundle\Entity\Invoice;
use Doctrine\ORM\EntityRepository;

class PaymentRepository extends EntityRepository
{

    /**
     * Returns an array of all the payments for an invoice
     *
     * @param Invoice $invoice
     *
     * @return array
     */
    public function getPaymentsForInvoice(Invoice $invoice)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            'p.id',
            'p.amount',
            'p.currency',
            'p.created',
            'm.name as method',
            's.name as status',
            's.label as status_label'
        )
        ->join('p.method', 'm')
        ->join('p.status', 's')
        ->where('p.invoice = :invoice')
        ->setParameter('invoice', $invoice);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }
}
