<?php

namespace CSBill\InvoiceBundle\Repository;

use CSBill\InvoiceBundle\Entity\Status;
use Doctrine\ORM\EntityRepository;

class InvoiceRepository extends EntityRepository
{
    public function getTotalIncome()
    {
        return $this->getTotalByStatus('paid');
    }

    public function getTotalOutstanding()
    {
        return $this->getTotalByStatus('pending');
    }

    public function getTotalByStatus($status)
    {
        if(!$status instanceof Status) {
            $status = $this->getEntityManager()->getRepository('CSBillInvoiceBundle:Status')->findOneByName($status);
        }

        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.total)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }
}