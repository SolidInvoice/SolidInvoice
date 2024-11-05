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

namespace SolidInvoice\CoreBundle\Company;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function assert;

final class CompanySelector
{
    private ?Ulid $companyId = null;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function getCompany(): ?Ulid
    {
        return $this->companyId;
    }

    public function switchCompany(Ulid $companyId): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $em
            ->getFilters()
            ->enable('company')
            ->setParameter('companyId', $companyId, UlidType::NAME);

        $this->companyId = $companyId;
    }
}
