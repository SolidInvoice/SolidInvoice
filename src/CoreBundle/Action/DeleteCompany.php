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

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DeleteCompany extends AbstractController
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Check if the CSRF token is valid
        $csrfToken = $request->request->get('_csrf_token');

        if (! $this->isCsrfTokenValid('delete_company', $csrfToken)) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->companyRepository->deleteCompany($this->companySelector->getCompany());

        $session = $request->getSession();
        assert($session instanceof SessionInterface);
        $session->remove('company');

        $this->addFlash('success', 'Company deleted successfully.');

        return $this->redirectToRoute('_select_company');
    }
}
