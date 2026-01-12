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

namespace SolidInvoice\UserBundle\Onboarding\Form\Type;

use SolidInvoice\UserBundle\Onboarding\DTO\OnboardingData;
use SolidInvoice\UserBundle\Onboarding\Form\FormFlow\OnboardingNavigatorType;
use SolidInvoice\UserBundle\Onboarding\Form\Step\ClientSetupStep;
use SolidInvoice\UserBundle\Onboarding\Form\Step\CompanySetupStep;
use SolidInvoice\UserBundle\Onboarding\Form\Step\InvoiceSetupStep;
use Symfony\Component\Form\Flow\AbstractFlowType;
use Symfony\Component\Form\Flow\DataStorage\SessionDataStorage;
use Symfony\Component\Form\Flow\FormFlowBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OnboardingType extends AbstractFlowType
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function buildFormFlow(FormFlowBuilderInterface $builder, array $options): void
    {
        // Step 1: Company Setup (Required)
        $builder->addStep(
            name: 'company',
            type: CompanySetupStep::class,
            options: [
                'inherit_data' => true,
                'required' => true,
            ],
        );

        // Step 2: Client Setup (Optional)
        $builder->addStep(
            name: 'client',
            type: ClientSetupStep::class,
            options: [
                'inherit_data' => true,
                'required' => false,
            ],
        );

        $formData = $builder->getData();
        assert($formData instanceof OnboardingData);

        // Step 3: Invoice Setup (Optional - auto-skipped if client was skipped)
        $builder->addStep(
            name: 'invoice',
            type: InvoiceSetupStep::class,
            options: [
                'inherit_data' => true,
                'required' => false,
                'currency' => $formData->companyCurrency,
            ],
        );

        // Completion step (no form, just celebration)
        $builder->addStep(
            name: 'complete',
            options: ['mapped' => false]
        );

        // Add navigation buttons
        $builder->add(
            'navigator',
            OnboardingNavigatorType::class,
            options: [
                'next_button_label' => $formData->currentStep === 'invoice'
                    ? 'onboarding.navigation.create_invoice'
                    : 'onboarding.navigation.continue',
            ],
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OnboardingData::class,
            'data_storage' => new SessionDataStorage('user_onboarding', $this->requestStack),
            'step_property_path' => 'currentStep',
        ]);
    }
}
