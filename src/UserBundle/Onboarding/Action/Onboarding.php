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
use SolidInvoice\UserBundle\Onboarding\Form\Type\OnboardingType;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function assert;

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

        // If already completed, redirect to dashboard
        if ($this->onboardingManager->isOnboardingComplete($user)) {
            return $this->redirectToRoute('_dashboard');
        }

        // Initialize onboarding if not started
        $currentStep = $this->onboardingManager->getCurrentStep($user);

        if (! $currentStep) {
            $this->onboardingManager->startOnboarding($user);
        }

        // Create and handle form
        $form = $this->createForm(OnboardingType::class, new OnboardingData())
            ->handleRequest($request);

        assert($form instanceof FormFlowInterface);

        // Check if we're on the complete step after invoice submission (auto-complete with invoice data)

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getCursor()->getCurrentStep() === 'invoice') {
                $formData = $form->getData();
                assert($formData instanceof OnboardingData);

                // If we have invoice data, complete onboarding immediately and redirect to invoice
                if ($formData->invoiceDescription && $formData->invoiceAmount) {
                    $invoice = $this->onboardingManager->completeOnboarding($user, $formData);
                    $form->reset();

                    if ($invoice !== null) {
                        $this->addFlash('success', 'onboarding.flash.invoice_created');
                        return $this->redirectToRoute('_invoices_view', ['id' => $invoice->getId()]);
                    }
                }
            } elseif ($form->isFinished()) {
                $formData = $form->getData();
                assert($formData instanceof OnboardingData);

                // Save all data and get created invoice
                $invoice = $this->onboardingManager->completeOnboarding($user, $formData);

                // Clear form data from session
                $form->reset();

                // If an invoice was created, redirect to invoice detail page
                if ($invoice !== null) {
                    $this->addFlash('success', 'onboarding.flash.invoice_created');
                    return $this->redirectToRoute('_invoices_view', ['id' => $invoice->getId()]);
                }

                // Otherwise, redirect to dashboard
                $this->addFlash('success', 'onboarding.flash.onboarding_complete');
                return $this->redirectToRoute('_dashboard');
            } else {
                $this->onboardingManager->setCurrentStep($user, $form->getCursor()->getCurrentStep());
            }
        }

        $formData = $form->getData();
        assert($formData instanceof OnboardingData);

        // Render current step
        return $this->render('@SolidInvoiceUser/Onboarding/onboarding.html.twig', [
            'form' => $form->getStepForm(),
            'currentStep' => $form->getCursor()->getCurrentStep(),
            'progress' => $this->calculateProgress($form),
            'hasClient' => $formData->clientName !== null && $formData->clientName !== '',
        ]);
    }

    /**
     * Calculate progress percentage
     */
    private function calculateProgress(FormFlowInterface $form): int
    {
        $cursor = $form->getCursor();
        $totalSteps = count($cursor->getSteps());
        $currentPosition = $cursor->getStepIndex();

        return (int) (($currentPosition / $totalSteps) * 100);
    }
}
