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

namespace SolidInvoice\TaxBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<InvoiceTax>
 * @see \SolidInvoice\TaxBundle\Tests\Repository\InvoiceTaxRepositoryTest
 */
final class InvoiceTaxRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceTax::class);
    }
}
