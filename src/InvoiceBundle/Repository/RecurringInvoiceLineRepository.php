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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<RecurringInvoiceLine>
 */
final class RecurringInvoiceLineRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringInvoiceLine::class);
    }
}
