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

namespace SolidInvoice\CoreBundle\Action;

use Ramsey\Uuid\Uuid;
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

    /**
     * @return Template|RedirectResponse
     */
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

    public function switchCompany(string $id): RedirectResponse
    {
        $uuid = Uuid::fromString($id);

        $user = $this->security->getUser();

        assert($user instanceof User);

        $companies = $user->getCompanies();

        if ($companies->exists(static fn (int $key, Company $company) => ! $company->getId()->equals($uuid))) {
            $this->companySelector->switchCompany($uuid);
        }

        return new RedirectResponse($this->router->generate('_dashboard'));
    }
}
