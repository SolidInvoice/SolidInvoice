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

namespace SolidInvoice\UserBundle\Onboarding\Form\FormFlow;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Flow\ButtonFlow;
use Symfony\Component\Form\Flow\FormFlow;
use Symfony\Component\Form\Flow\FormFlowCursor;
use Symfony\Component\Form\Flow\Type\ButtonFlowType;
use Symfony\Component\Form\Flow\Type\FinishFlowType;
use Symfony\Component\Form\Flow\Type\NextFlowType;
use Symfony\Component\Form\Flow\Type\PreviousFlowType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OnboardingNavigatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'back',
            PreviousFlowType::class,
            [
                'label' => 'onboarding.navigation.back',
                'translation_domain' => 'onboarding',
                'attr' => ['class' => 'btn btn-link text-muted'],
                'include_if' => fn (FormFlowCursor $cursor): bool => $cursor->canMoveBack() && ! $cursor->isLastStep(),
            ]
        );

        $builder->add(
            'next',
            NextFlowType::class,
            [
                'label' => $options['next_button_label'],
                'translation_domain' => 'onboarding',
                'include_if' => fn (FormFlowCursor $cursor): bool => $cursor->canMoveNext() && ! $cursor->isLastStep(),
            ]
        );

        // Skip button for optional steps only (client and invoice)
        $builder->add(
            'skip',
            ButtonFlowType::class,
            [
                'label' => 'onboarding.navigation.skip',
                'translation_domain' => 'onboarding',
                'attr' => ['class' => 'btn btn-link text-muted', 'name' => 'skip'],
                'validation_groups' => false,
                'handler' => function (mixed $data, ButtonFlow $button, FormFlow $flow): void {
                    do {
                        $flow->moveNext();
                    } while (! $flow->getCursor()->isLastStep());
                },
                'include_if' => fn (FormFlowCursor $cursor): bool =>
                    in_array($cursor->getCurrentStep(), ['client', 'invoice'], true),
            ]
        );

        $builder->add(
            'finish',
            FinishFlowType::class,
            [
                'label' => 'onboarding.navigation.finish',
                'translation_domain' => 'onboarding',
                'attr' => ['class' => 'btn btn-primary btn-lg'],
                'include_if' => fn (FormFlowCursor $cursor): bool => $cursor->isLastStep(),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'mapped' => false,
            'priority' => -100,
            'next_button_label' => 'onboarding.navigation.continue',
        ]);
    }
}
