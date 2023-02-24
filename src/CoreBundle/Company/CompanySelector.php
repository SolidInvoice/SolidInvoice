<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Company;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

final class CompanySelector
{
    private RequestStack $requestStack;
    private ManagerRegistry $registry;

    public function __construct(RequestStack $requestStack, ManagerRegistry $registry)
    {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
    }

    public function getCompany(): ?int
    {
        return $this->requestStack->getSession()->get('companyId');
    }

    public function switchCompany(int $companyId): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $em
            ->getFilters()
            ->enable('company')
            ->setParameter('companyId', $companyId, Types::INTEGER );

        $session = $this->requestStack->getSession();

        $session->set('companyId', $companyId);
    }
}
