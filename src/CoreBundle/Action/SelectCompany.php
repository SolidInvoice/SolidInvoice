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

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Uid\Ulid;

final class SelectCompany
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @return Template|RedirectResponse
     */
    public function __invoke(Request $request)
    {
        $user = $this->security->getUser();

        assert($user instanceof User);

        $companies = $user->getCompanies();

        if ($companies->count() === 0) {
            return new RedirectResponse($this->router->generate('_create_company'));
        }

        if ($companies->count() === 1) {
            $request->getSession()->set('company', $companies->first()->getId());
            return new RedirectResponse($this->router->generate('_dashboard'));
        }

        return new Template('@SolidInvoiceCore/Company/select.html.twig', ['companies' => $companies]);
    }

    public function switchCompany(Request $request, string $id): RedirectResponse
    {
        $uuid = Ulid::fromString($id);

        $user = $this->security->getUser();

        assert($user instanceof User);

        $companies = $user->getCompanies();

        if ($companies->exists(static fn (int $key, Company $company) => $company->getId()->equals($uuid))) {
            $request->getSession()->set('company', $uuid);
            return new RedirectResponse($this->router->generate('_dashboard'));
        }

        throw new BadRequestHttpException('Invalid company');
    }
}
