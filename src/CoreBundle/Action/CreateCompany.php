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
use SolidInvoice\CoreBundle\Form\Type\CompanyType;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final class CreateCompany extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly CompanyRepository $companyRepository,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        $form = $this->createForm(CompanyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company = $form->getData();
            assert($company instanceof Company);

            $company->addUser($user);
            // @TODO: Set the user as the owner of the company

            $this->companyRepository->save($company);

            $request->getSession()->set('company', $company->getId());

            return new RedirectResponse($this->router->generate('_dashboard'));
        }

        return $this->render(
            '@SolidInvoiceCore/Company/create.html.twig',
            [
                'form' => $form,
                'allowCancel' => ! $user->getCompanies()->isEmpty(),
            ]
        );
    }
}
