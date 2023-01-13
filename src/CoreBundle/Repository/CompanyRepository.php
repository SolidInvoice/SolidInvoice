<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;

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

        if (null !== $company) {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.name', ':name')
                ->where('c.id = :id')
                ->setParameter('name', $value)
                ->setParameter('id', $company)
                ->getQuery()
                ->execute();
        }
    }
}
