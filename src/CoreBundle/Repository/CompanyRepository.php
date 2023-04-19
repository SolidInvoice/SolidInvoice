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

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;

/**
 * @extends ServiceEntityRepository<Company>
 */
final class CompanyRepository extends ServiceEntityRepository
{
    private CompanySelector $companySelector;

    public function __construct(ManagerRegistry $registry, CompanySelector $companySelector)
    {
        parent::__construct($registry, Company::class);
        $this->companySelector = $companySelector;
    }

    public function updateCompanyName(string $value): void
    {
        $company = $this->companySelector->getCompany();

        if ($company instanceof UuidInterface) {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.name', ':name')
                ->where('c.id = :id')
                ->setParameter('name', $value)
                ->setParameter('id', $company, UuidBinaryOrderedTimeType::NAME)
                ->getQuery()
                ->execute();
        }
    }
}
