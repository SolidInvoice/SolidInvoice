<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Action;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class SelectCompany
{
    private CompanySelector $companySelector;
    private Security $security;
    private RouterInterface $router;

    public function __construct(
        CompanySelector $companySelector,
        Security $security,
        RouterInterface $router
    ) {
        $this->companySelector = $companySelector;
        $this->security = $security;
        $this->router = $router;
    }

    public function __invoke()
    {
        $user = $this->security->getUser();

        assert($user instanceof User);

        $companies = $user->getCompanies();

        if ($companies->count() === 0) {
            return new RedirectResponse($this->router->generate('_create_company'));
        }

        if ($companies->count() === 1) {
            $this->companySelector->switchCompany($companies->first()->getId());

            return new RedirectResponse($this->router->generate('_dashboard'));
        }

        return new Template('@SolidInvoiceCore/company/select.html.twig', ['companies' => $companies]);
    }

    public function switchCompany(int $id): RedirectResponse
    {
        $user = $this->security->getUser();

        assert($user instanceof User);

        $companies = $user->getCompanies();

        if ($companies->exists(static fn (int $key, Company $company) => $company->getId() === $id)) {
            $this->companySelector->switchCompany($id);
        }

        return new RedirectResponse($this->router->generate('_dashboard'));
    }
}
