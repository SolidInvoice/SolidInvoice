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

namespace SolidInvoice\InvoiceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceContact;

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
