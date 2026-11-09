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

namespace SolidInvoice\UserBundle\Onboarding\Action;

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Onboarding\DTO\OnboardingData;
use SolidInvoice\UserBundle\Onboarding\Form\Step\CompanySetupStep;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class Onboarding extends AbstractController
{
    public function __construct(
        private readonly OnboardingManager $onboardingManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->onboardingManager->isOnboardingComplete($user)) {
            return $this->redirectToRoute('_dashboard');
        }

        $data = new OnboardingData();
        $form = $this->createForm(CompanySetupStep::class, $data)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company = $this->onboardingManager->completeOnboarding($user, $data);
            $request->getSession()->set('company', $company->getId());

            // The editor opens in new-client mode for a company with no clients,
            // so the first thing the user does is write and send a real invoice.
            $this->addFlash('info', 'onboarding.flash.first_invoice');

            return $this->redirectToRoute('_invoices_create');
        }

        return $this->render('@SolidInvoiceUser/Onboarding/onboarding.html.twig', [
            'form' => $form,
        ]);
    }
}
