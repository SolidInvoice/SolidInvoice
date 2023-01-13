<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Company;

use Symfony\Component\HttpFoundation\RequestStack;

final class CompanySelector
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getCompany(): ?int
    {
        $session = $this->requestStack->getSession();

        return $session->get('companyId');
    }

    public function switchCompany(int $companyId): void
    {
        $session = $this->requestStack->getSession();

        $session->set('companyId', $companyId);
    }
}
