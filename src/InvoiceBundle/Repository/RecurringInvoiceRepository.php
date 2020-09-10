<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;

class RecurringInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringInvoice::class);
    }

    public function getRecurringGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c');

        if (!empty($parameters['client'])) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $parameters['client']);
        }

        return $qb;
    }
}
