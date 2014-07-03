<?php

namespace CSBill\PaymentBundle\Repository;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use Doctrine\ORM\EntityRepository;

class PaymentRepository extends EntityRepository
{

    /**
     * Returns an array of all the payments for an invoice
     *
     * @param Invoice $invoice
     * @param string  $orderField
     * @param string  $sort
     *
     * @return array
     */
    public function getPaymentsForInvoice(Invoice $invoice, $orderField = null, $sort = 'DESC')
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.invoice = :invoice')
            ->setParameter('invoice', $invoice);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Returns an array of all the payments for a client
     *
     * @param Client  $client
     * @param string  $orderField
     * @param string  $sort
     *
     * @return array
     */
    public function getPaymentsForClient(Client $client, $orderField = null, $sort = 'DESC')
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.client = :client')
            ->setParameter('client', $client);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @param string $orderField
     * @param string $sort
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getPaymentQueryBuilder($orderField = null, $sort = 'DESC')
    {
        if (null === $orderField) {
            $orderField = 'p.created';
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            'p.id',
            'p.amount',
            'p.currency',
            'p.created',
            'p.completed',
            'i.id as invoice',
            'm.name as method',
            's.name as status',
            's.label as status_label',
            'p.message'
        )
            ->join('p.method', 'm')
            ->join('p.status', 's')
            ->join('p.invoice', 'i')
            ->orderBy($orderField, $sort);

        return $queryBuilder;
    }
}
